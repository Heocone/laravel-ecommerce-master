<?php

namespace App\Http\Controllers;
use App\Models\Banner;

use App\Models\Config;
use App\Models\CmsNews;
use App\Models\CmsPage;
use App\Models\ShopBrand;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use App\Models\ShopOrderTotal;
use App\Models\ShopOrderDetail;
use App\Models\ShopOrderHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use App\Promocodes\Facades\Promocodes;
use Gloudemans\Shoppingcart\Facades\Cart;



class VNPAYController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public $banners;
    public $news;
    public $notice;
    //////
    public $brands;
    public $categories;
    public $configs;
    public $theme       = "247";
    public $theme_asset = "247";

    public function __construct()
    {
        $host = request()->getHost();
        config(['app.url' => 'http://' . $host]);
        //End demo multihost
        $this->banners = Banner::where('status', 1)->orderBy('sort', 'desc')->orderBy('id', 'desc')->get();
        $this->news    = (new CmsNews)->getItemsNews($limit = 8, $opt = 'paginate');
        $this->notice  = (new CmsPage)->where('uniquekey', 'notice')->where('status', 1)->first();
        //////

        $this->brands     = ShopBrand::getBrands();
        $this->categories = ShopCategory::getCategories(0);
        $this->configs    = Config::pluck('value', 'key')->all();
//Config for  SMTP
        config(['app.name' => $this->configs['site_title']]);
        config(['mail.driver' => ($this->configs['smtp_mode']) ? 'smtp' : 'sendmail']);
        config(['mail.host' => empty($this->configs['smtp_host']) ? env('MAIL_HOST', '') : $this->configs['smtp_host']]);
        config(['mail.port' => empty($this->configs['smtp_port']) ? env('MAIL_PORT', '') : $this->configs['smtp_port']]);
        config(['mail.encryption' => empty($this->configs['smtp_security']) ? env('MAIL_ENCRYPTION', '') : $this->configs['smtp_security']]);
        config(['mail.username' => empty($this->configs['smtp_user']) ? env('MAIL_USERNAME', '') : $this->configs['smtp_user']]);
        config(['mail.password' => empty($this->configs['smtp_password']) ? env('MAIL_PASSWORD', '') : $this->configs['smtp_password']]);
        config(['mail.from' =>
            ['address' => $this->configs['site_email'], 'name' => $this->configs['site_title']]]
        );
//
        View::share('categories', $this->categories);
        View::share('brands', $this->brands);
        View::share('banners', $this->banners);
        View::share('configs', $this->configs);
        View::share('theme_asset', $this->theme_asset);
        View::share('theme', $this->theme);
        View::share('products_hot', (new ShopProduct)->getProducts($type = 1, $limit = 4, $opt = 'random'));
        View::share('logo', Banner::where('status', 1)->where('type', 0)->orderBy('sort', 'desc')->orderBy('id', 'desc')->first());
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    // public function vnpay_pay(Request $request)
    // {

    //     return view('vnpay_php.vnpay_php.index');
    // }


    // public function index(Request $request)
    // {
    //     DB::connection('mysql')->beginTransaction();
    //         $objects                     = array();
    //         $objects[]                   = (new ShopOrderTotal)->getShipping(); //module shipping
    //         $objects[]                   = (new ShopOrderTotal)->getDiscount(); //module discount
    //         $objects[]                   = (new ShopOrderTotal)->getReceived(); //module reveived
    //         $dataTotal                   = ShopOrderTotal::processDataTotal($objects); //sumtotal and re-sort item total
    //         $subtotal                    = (new ShopOrderTotal)->sumValueTotal('subtotal', $dataTotal);
    //         $shipping                    = (new ShopOrderTotal)->sumValueTotal('shipping', $dataTotal); //sum shipping
    //         $discount                    = (new ShopOrderTotal)->sumValueTotal('discount', $dataTotal); //sum discount
    //         $received                    = (new ShopOrderTotal)->sumValueTotal('received', $dataTotal); //sum received
    //         $total                       = (new ShopOrderTotal)->sumValueTotal('total', $dataTotal);
    //         $toname          = $request->input('toname');
    //         $address1        = $request->input('address1');
    //         $address2        = $request->input('address2');
    //         $phone           = $request->input('phone');
    //         $comment         = $request->input('comment');
    //         dd($toname);

    //     return view('vnpay_php.vnpay_php.vnpay_pay',compact('total'));
    // }

    public function vnpay_create_payment(Request $request)
    {
            //truyen du lieu tu index

            $toname          = $request->input('toname');
            $address1        = $request->input('address1');
            $address2        = $request->input('address2');
            $phone           = $request->input('phone');
            $comment         = $request->input('comment');

            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            date_default_timezone_set('Asia/Ho_Chi_Minh');

            /**
             * 
             *
             * @author CTT VNPAY
             */
            $vnp_TmnCode = "3C5MVIN9"; //Mã định danh merchant kết nối (Terminal Id)
            $vnp_HashSecret = "PJZLTAFYHGEMOBTTUVTTIQKJZXJXUPKP"; //Secret key
            $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
            $vnp_Returnurl = "http://127.0.0.1:8000/return";
            $vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
            $apiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";
            //Config input format
            //Expire
            $startTime = date("YmdHis");
            $expire = date('YmdHis',strtotime('+15 minutes',strtotime($startTime)));

            $vnp_TxnRef = rand(1,10000); //Mã giao dịch thanh toán tham chiếu của merchant
            $vnp_Amount = $_POST['amount']; // Số tiền thanh toán
            $vnp_Locale = $_POST['language']; //Ngôn ngữ chuyển hướng thanh toán
            $vnp_BankCode = $_POST['bankCode']; //Mã phương thức thanh toán
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR']; //IP Khách hàng thanh toán
            
            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount* 100,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" =>$vnp_TxnRef,
                "vnp_OrderType" => "other",
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef,
                "vnp_ExpireDate"=>$expire,
            );

            if (isset($vnp_BankCode) && $vnp_BankCode != "") {
                $inputData['vnp_BankCode'] = $vnp_BankCode;
            }

            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);//  
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            header('Location: ' . $vnp_Url);
            die();
    }

    public function return()
    {
        return view('vnpay_php.vnpay_php.vnpay_return');
    }

    public function complete(Request $request){
        $tinhtrang = $request->input('KETQUA');
        if ($tinhtrang == "GD KHONG THANH CONG") {
            return redirect('gio-hang.html')->with('message1', 'Đặt hàng không thành công do quý khách hủy đơn thanh toán');
        }
        try {
            $payment_method = 'VNPAY';
            $transaction = 'GD THANH CONG';
            DB::connection('mysql')->beginTransaction();
            $objects                     = array();
            $objects[]                   = (new ShopOrderTotal)->getShipping(); //module shipping
            $objects[]                   = (new ShopOrderTotal)->getDiscount(); //module discount
            $objects[]                   = (new ShopOrderTotal)->getReceived(); //module reveived
            $dataTotal                   = ShopOrderTotal::processDataTotal($objects); //sumtotal and re-sort item total
            $subtotal                    = (new ShopOrderTotal)->sumValueTotal('subtotal', $dataTotal);
            $shipping                    = (new ShopOrderTotal)->sumValueTotal('shipping', $dataTotal); //sum shipping
            $discount                    = (new ShopOrderTotal)->sumValueTotal('discount', $dataTotal); //sum discount
            $received                    = (new ShopOrderTotal)->sumValueTotal('received', $dataTotal); //sum received
            $total                       = (new ShopOrderTotal)->sumValueTotal('total', $dataTotal);
            $arrOrder['user_id']         = empty(Auth::user()->id) ? 0 : Auth::user()->id;
            $arrOrder['subtotal']        = $subtotal;
            $arrOrder['shipping']        = $shipping;
            $arrOrder['discount']        = $discount;
            $arrOrder['received']        = $received;
            $arrOrder['payment_status']  = 0;
            $arrOrder['shipping_status'] = 0;
            $arrOrder['status']          = 0;
            $arrOrder['total']           = $total;
            $arrOrder['balance']         = $total + $received;
            // $arrOrder['toname']          = $request->get('toname');
            // $arrOrder['address1']        = $request->get('address1');
            // $arrOrder['address2']        = $request->get('address2');
            // $arrOrder['phone']           = $request->get('phone');
            // $arrOrder['comment']         = $request->get('comment');
            $arrOrder['toname']          = 'toname';
            $arrOrder['address1']        = 'address1';
            $arrOrder['address2']        = 'address2';
            $arrOrder['phone']           = 'phone';
            $arrOrder['comment']         = 'comment';
            $arrOrder['payment_method']  = $payment_method;
            $arrOrder['transaction']      = $transaction;
            // $arrOrder['created_at']      = date('Y-m-d H:i:s');

            //Insert to Order
            $orderId = ShopOrder::insertGetId($arrOrder);
            //

            //Insert order total
            ShopOrderTotal::insertTotal($dataTotal, $orderId);
            //End order total

            foreach (Cart::content() as $value) {
                $product                  = ShopProduct::find($value->id);
                $arrDetail['order_id']    = $orderId;
                $arrDetail['product_id']  = $value->id;
                $arrDetail['name']        = $value->name;
                $arrDetail['price']       = $value->price;
                $arrDetail['qty']         = $value->qty;
                $arrDetail['type']        = $value->options->toJson();
                $arrDetail['sku']         = $product->sku;
                $arrDetail['total_price'] = $value->price * $value->qty;
                $arrDetail['created_at']  = date('Y-m-d H:i:s');
                ShopOrderDetail::insert($arrDetail);
                //If product out of stock
                if (!$this->configs['product_buy_out_of_stock'] && $product->stock < $value->qty) {
                    return redirect('/')->with('error', 'Mã hàng ' . $product->sku . ' vượt quá số lượng cho phép');
                } //
                $product->stock -= $value->qty;
                $product->sold += $value->qty;
                $product->save();

            }

            Cart::destroy(); // destroy cart

            if (!empty(session('coupon'))) {
                Promocodes::apply(session('coupon'), $uID = null, $msg = 'Order #' . $orderId); // apply coupon
                $request->session()->forget('coupon'); //destroy coupon
            }

            //Add history
            $dataHistory = [
                'order_id' => $orderId,
                'content'  => 'New order',
                'user_id'  => empty(Auth::user()->id) ? 0 : Auth::user()->id,
                'add_date' => date('Y-m-d H:i:s'),
            ];
            ShopOrderHistory::insert($dataHistory);

            DB::connection('mysql')->commit();

            //Send email
            try {
                $data = ShopOrder::with('details')->find($orderId)->toArray();
                Mail::send('vendor.mail.order_new', $data, function ($message) use ($orderId) {
                    $message->to($this->configs['site_email'], $this->configs['site_title']);
                    $message->replyTo($this->configs['site_email'], $this->configs['site_title']);
                    $message->subject('[#' . $orderId . '] Đơn hàng mới!');
                });
            } catch (\Exception $e) {
                //
            } //

            return redirect('gio-hang.html')->with('message', 'ĐƠN HÀNG THÀNH CÔNG');
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            echo 'Caught exception: ', $e->getMessage(), "\n";

        }
    }

}
