<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\IncomingChatRequest;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\CustomerDeal;
use App\Models\Page;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function incoming(IncomingChatRequest $req, OpenAIService $ai)
    {
        $productCode = $req->input('product_code','combo');
        $currency    = $req->input('currency', config('app.default_currency', env('DEFAULT_CURRENCY','VND')));
    
        $pageData = Page::where('page_id', $req->input('page_id'))->first();

        $defaultPrice= (float) $pageData->price_per_unit ?? env('DEFAULT_UNIT_PRICE', 150000);
        $defaultComboPrice= (float) $pageData->price_per_combo ?? env('DEFAULT_COMBO_PRICE', 300000);
        $priceInput = $req->input('deal_price', $defaultPrice);
        $priceComboInput = $req->input('price_combo', $defaultComboPrice);
        $customer = DB::transaction(function () use ($req, $priceInput, $priceComboInput, $pageData) {
            $customer = Customer::query()
                ->firstOrCreate(
                    [
                        'external_id' => $req->string('customer_external_id'),
                        'page_id'     => $pageData->page_id,
                    ],
                    [
                        'name'  => $req->string('customer_name'),
                        'phone' => $req->string('customer_phone'),
                        'meta'  => null,
                    ]
                );
            if(!empty($customer->deals()->first())) {
                // Nếu có deal thì không cần tạo mới
            }
            else
            {
                // Nếu không có deal thì tạo mới deal với giá mặc định
                CustomerDeal::create([
                    'customer_id' => $customer->id,
                    'product_code'=> "",
                    'price'       => (float) $priceInput,
                    'price_combo' => (float) $priceComboInput,
                    'currency'    => $req->input('currency', env('DEFAULT_CURRENCY','VND')),
                    'source'      => 'api-inbound',
                ]);
            }
            // Nếu có deal_price trong request thì upsert deal cho KH này
            if ($req->filled('deal_price')) {
                CustomerDeal::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                    ],
                    [
                        'price'    => (float) $priceInput,
                        'price_combo'    => (float) $priceComboInput,
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
                    'price_combo'   => $req->input('price_combo'),
                ],
            ]);

            return $customer;
        });

        $deal = $customer->deals()->first();
        $price = $deal && !$deal->isExpired() ? (float)$deal->price : $priceInput;
        $price_combo = $deal && !$deal->isExpired() ? (float)$deal->price_combo : $priceComboInput;
        
        $contextByPage = $pageData->ai_context ?? '';
        $system = [
            $contextByPage
        ];

        $contextText = sprintf(
            'CONTEXT:\n- Khách hàng tên là: %s, Giá combo áp dụng cho khách này: %sVND, Giá lẻ áp dụng cho khách này là: %sVND\n, tổng tiền bằng giá bán cộng phí vận chuyển phía trên - Yêu cầu: trả lời tự nhiên, lịch sự, không thay đổi giá, dựa vào câu hỏi là combo hay giá lẻ để lấy giá đúng. Thêm các khuyến mãi nếu có và chính sách giao hàng (báo rõ phí giao hàng trong từng tin nhắn). Phải gọi tên khách hàng trong câu trả lời. ',
            $customer->name, number_format($price_combo,0,',','.'), number_format($price,0,',','.'), $currency
        );

        $userContent = $contextText . "\n\nCÂU KHÁCH HỎI: " . $req->input('message');

        $reply = $ai->chat($pageData->page_id, $userContent, $system);

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
            'applied_price'  => $price,
            'applied_price_combo'  => $price_combo,
            'currency'       => $currency,
            'ai_reply'       => $reply,
        ]);
    }
}
