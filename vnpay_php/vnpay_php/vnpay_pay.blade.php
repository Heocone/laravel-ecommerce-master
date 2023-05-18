<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
        <meta name="description" content="">
        <meta name="author" content="">
        <title>Tạo mới đơn hàng</title>
        <!-- Bootstrap core CSS -->
        <link href="{{ asset('bootstrap.min.css') }}" rel="stylesheet"/>
        <!-- Custom styles for this template -->
        <link href="{{ asset('jumbotron-narrow.css') }}" rel="stylesheet">  
        <script src="{{ asset('jquery-1.11.3.min.js') }}"></script>
    </head>

    <body>
        <?php date_default_timezone_set('Asia/Ho_Chi_Minh');
        /*
         * To change this license header, choose License Headers in Project Properties.
         * To change this template file, choose Tools | Templates
         * and open the template in the editor.
         */
          
        $vnp_TmnCode = "3C5MVIN9"; //Mã định danh merchant kết nối (Terminal Id)
        $vnp_HashSecret = "PJZLTAFYHGEMOBTTUVTTIQKJZXJXUPKP"; //Secret key
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = "http://controller-app.test/return";
        $vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
        $apiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";
        //Config input format
        //Expire
        $startTime = date("YmdHis");
        $expire = date('YmdHis',strtotime('+15 minutes',strtotime($startTime))); 
        ?> 
                   
        <div class="container">
        <h3>Tạo mới đơn hàng</h3>
            <div class="table-responsive">
                <form action="{{ url('/vnpay_create_payment') }}" id="frmCreateOrder" method="POST">
                    {{ csrf_field() }}       
                    <div class="form-group">
                        {{-- @foreach($cart as $item) --}}
                        <label for="amount">Số tiền</label>
                        <input class="form-control" data-val="true" data-val-number="The field Amount must be a number." data-val-required="The Amount field is required." id="amount" max="100000000" min="1" name="amount" type="number" value="{{ $total }}" readonly/>
                        <input type="hidden" class="toname" name="toname" value="{{ $toname }}">
                        <input type="hidden" class="address1" name="address1" value="{{ $address1 }}">
                        <input type="hidden" class="address2" name="address2" value="{{ $address2 }}">
                        <input type="hidden" class="phone" name="phone" value="{{ $phone }}">
                        <input type="hidden" class="comment" name="comment" value="{{ $comment }}">
                        
                        {{-- @endforeach --}}
                        
                    </div>
                     <h4>Chọn phương thức thanh toán</h4>
                    <div class="form-group">
                        <h5>Cách 1: Chuyển hướng sang Cổng VNPAY chọn phương thức thanh toán</h5>
                       <input type="radio" Checked="True" id="bankCode" name="bankCode" value="">
                       <label for="bankCode">Cổng thanh toán VNPAYQR</label><br>
                       
                       <h5>Cách 2: Tách phương thức tại site của đơn vị kết nối</h5>
                       <input type="radio" id="bankCode" name="bankCode" value="VNPAYQR">
                       <label for="bankCode">Thanh toán bằng ứng dụng hỗ trợ VNPAYQR</label><br>
                       
                       <input type="radio" id="bankCode" name="bankCode" value="VNBANK">
                       <label for="bankCode">Thanh toán qua thẻ ATM/Tài khoản nội địa</label><br>
                       
                       <input type="radio" id="bankCode" name="bankCode" value="INTCARD">
                       <label for="bankCode">Thanh toán qua thẻ quốc tế</label><br>
                       
                    </div>
                    <div class="form-group">
                        <h5>Chọn ngôn ngữ giao diện thanh toán:</h5>
                         <input type="radio" id="language" Checked="True" name="language" value="vn">
                         <label for="language">Tiếng việt</label><br>
                         <input type="radio" id="language" name="language" value="en">
                         <label for="language">Tiếng anh</label><br>
                         
                    </div>
                    <button type="submit" class="btn btn-default" href>Thanh toán</button>
                </form>
            </div>
            <p>
                &nbsp;
            </p>
            <footer class="footer">
                <p>&copy; VNPAY 2020</p>
            </footer>
        </div>  
    </body>
</html>
