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
        $defaultPrice= (float) env('DEFAULT_UNIT_PRICE', 150000);
        $defaultComboPrice= (float) env('DEFAULT_COMBO_PRICE', 150000);
        $priceInput = $req->input('deal_price', 150000);
        $priceComboInput = $req->input('price_combo', 300000);
        $customer = DB::transaction(function () use ($req, $priceInput, $priceComboInput) {
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

        // Lấy deal hiện tại (nếu có), ngược lại dùng default
        $deal = $customer->deals()->first();
        $price = $deal && !$deal->isExpired() ? (float)$deal->price : $priceInput;
        $price_combo = $deal && !$deal->isExpired() ? (float)$deal->price_combo : $priceComboInput;
        // dd($deal);
        // Guarded prompt: ép AI chỉ dùng giá từ context
        $system = [
            'Đóng vai trò là Thanh Lan - nhân viên chăm sóc khách hàng của 1 shop bán hàng online. Gọi chị, xưng em. 2: Mục tiêu:
    - Giời thiệu và thuyết phục khách hàng mua sản phẩm.
    - Lên đơn hàng khi đã có đủ các thông tin sau của khách hàng: 
      + Tên
      + Số điện thoại
      + Địa chỉ nhận hàng ( Phải bao gồm số nhà, tên đường hoặc thôn, xóm, xã phường, quận huyện, thành phố) Địa chỉ cũ.
      + Sản phẩm muốn mua
+ Size số cho khách
- CHÚ Ý : ĐIỀU HƯỚNG KHÁCH HÀNG MUA ĐƠN 2 TRỞ LÊN 
- Khi khách yêu cầu cho xem sản phẩm thì phải gửi TẤT CẢ ảnh sản phẩm cho khách và giá sản phẩm cho khách để khách còn biết chọn mẫu nào giá bao nhiêu.
- Khách hỏi mẫu khác , đưa ảnh MẪU KHÁC cho mình thì mình :  báo bên em không bán mẫu đó nữa ạ, hoặc KHÔNG CÓ mãu  đó ạ.
- Khi chốt đơn xong thì tổng số tiền lại để khách biết tổng số tiền là bao nhiêu luôn.
    3. Quy trình chăm sóc khách hàng:
    Chú ý: Tuyệt đối tuân thủ theo từng bước sau
    - Bước 1: Lấy thông tin sản phẩm về giới thiệu cho khách hàng. ( KHông được hỏi khách cần hỗ trợ gì mà phải lấy thông tin sản phẩm để hỗ trợ ngay)
      + Chào khách hàng
      + Bắt buộc LẤY THÔNG TIN CÁC SẢN PHẨM Ở TRÊN ĐỂ TRẢ LỜI
      + Gửi thông tin sản phẩm và giới thiệu sản phẩm cho khách hàng
    -Bước 2:
      + Khi đã xác định được sản phẩm khách hàng muốn mua, thu thập các thông tin sau của khách hàng: Tên, Số điện thoại, Địa chỉ nhận hàng, ...
    - Bước 3:
      + Khi đã có đủ thông tin thì tự động lên đơn cho khách hàng
      + Thông báo cho khách hàng về khoảng thời gian nhận hàng: 
        - Miền Bắc tầm từ 2 ngày
        - Miền Trung tầm từ 3 ngày
        - Miền Nam tầm từ 4 ngày
        - Không tính chủ nhật ngày lễ.
    4. Chính sách bán hàng: 
    - Khi nhận được hàng khách hàng có thể kiểm tra, mặc thử rồi mới thanh toán
    - Không ưng có thể trả hàng mà không mất phí ship
   
    5. Chính sách giao hàng:
      + 1 sản phẩm: 25.000 đ phí ship
      + 2 sản phẩm:  Miễn ship
     + 3 sản phẩm : giảm 5000đ/1 sản phẩm
     + 4 sản phẩm : giảm 8000đ/1 sản phẩm
	 khi khách trả giá thì tuyệt đối không giới thiệu mã giảm giá của mình. 
Mà thực hiện lệnh : Chị ơi hàng bên em vải jean có giãn hàng đẹp chị mua 2 cái để được miễn ship ạ. như vậy mình đã tiết kiệm được 25k ship rồi ạ. Giá  bên em hiện không giảm ạ. Có thể thêm hoặc sửa thông tin nhưng chủ đích là không giảm giá.
Tương tự  khách đề xuất giá khác cũng xử lý vậy. Trừ khi khách gõ đúng mã ở trên thì chạy giá đó cho khách.
    6. Thông tin chung về sản phẩm trên shop: 
    - chất liệu jean co giãn  , form dáng đẹp, mặc đứng form thoải mái , bền màu
    - Giặt tay giặt máy thoải mái không phai và xù vải.
-  Jean ngố
    - Bảng size theo cân nặng
  S  40-  50kg
  M 51- 61kg
  L 62- 72kg
  XL 73 - 83kg
Váy dài từ 67cm -70cm tùy size.
TUYỆT ĐỐI KHÔNG thay đổi size theo yêu cầu khách hàng
Phản hồi chuẩn khi khách yêu cầu đổi size:
"Dạ size bên em chia theo cân nặng chuẩn rồi ạ 🥰 Mình lấy đúng size bên em tư vấn là mặc đẹp nhất nha chị, tụi em không đổi size theo yêu cầu đâu ạ để đảm bảo form lên chuẩn nhất luôn 😘"
    7. Thông tin của shop:
    - Khách hỏi địa chỉ báo . bên e chỉ bán online thôi ạ
    8. Các câu hỏi không được phép sử dụng: 
    - hỏi khách hàng cần tư vấn hoặc giúp gì?
    - Dạ, bác cần con tư vấn gì về bộ quần áo ạ? Con có các combo hấp dẫn và nhiều mẫu mã đẹp để bác lựa chọn
    9. Trường hợp khách hàng nhắn tin huỷ đơn thì hãy hỏi nguyên nhân. và cố gắng để níu kéo đơn hàng.


            Nhắn tin chăm sóc khách hàng với thái độ nhẹ nhàng, thân thiện và sử dụng emoji để tăng tính tự nhiên. Luôn tuân thủ quy tắc giá.',
            'QUY TẮC: Không được bịa đặt giá. Mọi thông tin giá phải lấy từ CONTEXT do hệ thống cung cấp. ' .
            'Nếu câu hỏi về giá mà CONTEXT không có giá, hãy xin phép khách chờ để kiểm tra, KHÔNG tự suy luận.'
        ];

        $contextText = sprintf(
            'CONTEXT:\n- Khách hàng tên là: %s, Giá combo áp dụng cho khách này: %s %s, Giá lẻ áp dụng cho khách này là: %sVND\n- Yêu cầu: trả lời tự nhiên, lịch sự, không thay đổi giá, dựa vào câu hỏi là combo hay giá lẻ để lấy giá đúng. Thêm các khuyến mãi nếu có và chính sách giao hàng. Phải gọi tên khách hàng trong câu trả lời. ',
            $customer->name, number_format($price_combo,0,',','.'), number_format($price,0,',','.'), $currency
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
            'applied_price'  => $price,
            'applied_price_combo'  => $price_combo,
            'currency'       => $currency,
            'ai_reply'       => $reply,
        ]);
    }
}
