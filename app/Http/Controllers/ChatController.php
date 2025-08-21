<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\IncomingChatRequest;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\CustomerDeal;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function incoming(IncomingChatRequest $req, OpenAIService $ai)
    {
        $productCode = $req->input('product_code','combo');
        $currency    = $req->input('currency', config('app.default_currency', env('DEFAULT_CURRENCY','VND')));
        $defaultPrice= (float) env('DEFAULT_COMBO_PRICE', 300000);

        $customer = DB::transaction(function () use ($req) {
            $customer = Customer::query()
                ->firstOrCreate(
                    [
                        'external_id' => $req->string('customer_external_id'),
                        'page_id'     => $req->string('page_id')
                    ],
                    [
                        'name'  => $req->string('customer_name'),
                        'phone' => $req->string('customer_phone'),
                        'meta'  => null,
                    ]
                );

            // Nếu có deal_price trong request thì upsert deal cho KH này
            if ($req->filled('deal_price')) {
                CustomerDeal::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'product_code'=> $req->input('product_code','combo')
                    ],
                    [
                        'price'    => (float) $req->input('deal_price'),
                        'currency' => $req->input('currency', env('DEFAULT_CURRENCY','VND')),
                        'source'   => 'api-inbound',
                    ]
                );
            }

            // Log inbound message
            ChatMessage::create([
                'customer_id' => $customer->id,
                'external_id' => $req->input('message_external_id'),
                'direction'   => 'inbound',
                'content'     => $req->input('message'),
                'context'     => [
                    'page_id'      => $req->input('page_id'),
                    'product_code' => $req->input('product_code'),
                    'deal_price'   => $req->input('deal_price'),
                ],
            ]);

            return $customer;
        });

        // Lấy deal hiện tại (nếu có), ngược lại dùng default
        $deal = $customer->deals()->where('product_code', $productCode)->first();
        $price = $deal && !$deal->isExpired() ? (float)$deal->price : $defaultPrice;

        // Guarded prompt: ép AI chỉ dùng giá từ context
        $system = [
            'Bạn là tư vấn viên bán hàng. Luôn tuân thủ quy tắc giá.',
            'QUY TẮC: Không được bịa đặt giá. Mọi thông tin giá phải lấy từ CONTEXT do hệ thống cung cấp. ' .
            'Nếu câu hỏi về giá mà CONTEXT không có giá, hãy xin phép khách chờ để kiểm tra, KHÔNG tự suy luận.'
        ];

        $contextText = sprintf(
            'CONTEXT:\n- Sản phẩm: %s\n- Giá áp dụng cho khách này: %s %s\n- Yêu cầu: trả lời tự nhiên, lịch sự, không thay đổi giá. ',
            $productCode, number_format($price,0,',','.'), $currency
        );

        $userContent = $contextText . "\n\nCÂU KHÁCH HỎI: " . $req->input('message');

        $reply = $ai->chat($userContent, $system);

        // Log outbound
        ChatMessage::create([
            'customer_id' => $customer->id,
            'direction'   => 'outbound',
            'content'     => $reply,
            'context'     => [
                'product_code' => $productCode,
                'applied_price'=> $price,
                'currency'     => $currency,
                'model'        => config('services.openai.model'),
            ],
        ]);

        return response()->json([
            'success'        => true,
            'customer_id'    => $customer->id,
            'product_code'   => $productCode,
            'applied_price'  => $price,
            'currency'       => $currency,
            'ai_reply'       => $reply,
        ]);
    }
}
