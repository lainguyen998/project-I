<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Slide;
use App\Product;
use App\ProductType;
use App\Cart;
use Session;
use App\Customer;
use App\Bill;
use App\BillDetail;
use App\User;
use Hash;
use Auth;
class PageController extends Controller
{
    public function getIndex(){
        $slide = Slide::all();
        //print_r($slide);
        //exit;
        $new_product = Product::where('new',1)->paginate(4);
        $sanpham_khuyenmai = Product::where('promotion_price','<>','0')->paginate(8);
    	return view('page.trangchu',compact('slide','new_product','sanpham_khuyenmai'));
    } 

    public function getLoaisanpham($type){
        $sp_theoloai = Product::where('id_type',$type)->get();
        $sp_khac = Product::where('id_type','<>',$type)->paginate(3);
        $loai = ProductType::all();
        $loai_sp = ProductType::where('id',$type)->first();
    	return view('page.loai_sanpham',compact('sp_theoloai','sp_khac','loai','loai_sp'));
    }

    public function getChitietsanpham(Request $req){
        $sanpham = Product::where('id',$req->id)->first();
        $sptt = Product::where('id_type',$sanpham->id_type)->paginate(6);
    	return view('page.chitiet_sanpham',compact('sanpham','sptt'));
    }

    public function getLienhe(){
    	return view('page.lienhe');
    }

    public function getGioithieu(){
    	return view('page.gioithieu');
    }

    public function getAddtoCart(Request $req,$id){
        if(Session::get('login') == NULL || Session::get('login') == ''){
            $sessionlogin = 'false';
        }
        else $sessionlogin = 'true';
        if($sessionlogin == 'true')
        {   
            $product = Product::find($id);
            $oldCart = Session('cart')?Session::get('cart'):null;
            $cart = new Cart($oldCart);
            $cart->add($product,$id);
            $req->session()->put('cart',$cart);
            return redirect()->back();
        }
        else return redirect('/dang-nhap')->with(['flag'=>'success','message'=>'Bạn cần đăng nhập để thêm sản phầm vào giỏ hàng']);
        
    }
    public function getDelitemcart($id){
        $oldCart = Session::has('cart')?Session::get('cart'):null;
        $cart = new Cart($oldCart);
        $cart->removeItem($id);
        if(count($cart->items)>0){
            Session::put('cart',$cart);
        }
        else{
            Session::forget('cart');
        }
        return redirect()->back();
    }

    public function getDathang(){
         if(Session('cart')){
            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);
            //dd($cart);
            return view('page.dathang',['product_cart'=>$cart->items,'totalPrice'=>$cart->totalPrice,'totalQty'=>$cart->totalQty]);
        }
          else{
            return view('page.dathang');
          }
    }

    public function postDathang(Request $req){
      //  $this->validate($req,
       //     [
        //        'full_name'=>'required',
         //       'gender'=>'required',
         //       'email'=>'required|email',
          //      'address'=>'required',
          //      'phone'=>'required'
//
          //  ],
          //  [
            //    'full_name.required'=>'Bạn chưa điền tên',
           //     'gender.required'=>'Bạn chưa chọn giới tính',
           //     'email.required'=>'Bạn chưa điền email',
           //     'email.email'=>'Email không đúng',
           //     'address.required'=>'Bạn chưa điền địa chỉ',
            //    'phone.required'=>'Bạn chưa điền số điện thoại'
           // ]);
        $cart = Session::get('cart');
        //dd($cart);
        $customer = new Customer;
        $customer->name = $req->full_name;
        $customer->gender = $req->gender;
        $customer->email = $req->email;
        $customer->address = $req->address;
        $customer->phone_number = $req->phone;
        $customer->note = $req->note;
        $customer->save();

        $bill = new Bill;
        $bill->id_customer = $customer->id;
        $bill->date_order = date('Y-m-d');
        $bill->total = $cart->totalPrice;
        $bill->payment = $req->payment_method;
        $bill->note = $req->note; 
        $bill->save();

        foreach($cart->items as $key=>$value){
            $bill_detail = new BillDetail;
            $bill_detail->id_bill = $bill->id;
            $bill_detail->id_product = $key;
            $bill_detail->quantity = $value['qty'];
            $bill_detail->unit_price = $value['price']/$value['qty'];
            $bill_detail->save();
        }

        Session::forget('cart');
        return redirect()->back()->with('thongbao','Đặt hàng thành công');
    }

    public function getDangnhap(){
        return view('page.login');
    }

    public function postDangnhap(Request $req){
        $this->validate($req,
            [
                'email'=>'required|email',
                'password'=>'required|min:6|max:20'
            ],
            [
                'email.required'=>'Vui lòng nhập Email',
                'email.email'=>'Email không đúng định dạng',
                'password.required'=>'Vui lòng nhập mật khẩu',
                'password.min'=>'Mật khẩu ít nhất 6 kí tự',
                'password.max'=>'Mật khẩu tối đa 20 kí tự'
            ]);
        $credentials = array('email'=>$req->email,'password'=>$req->password);
        if(Auth::attempt($credentials)){
            Session::put('login', 'true');
            return redirect()->back()->with(['flag'=>'success','message'=>'Đăng nhập thành công']);
        }
        else{
            return redirect()->back()->with(['flag'=>'danger','message'=>'Đăng nhập thất bại']);
        }
    }

    public function getDangki(){
        return view('page.dangki');
    }

     public function postDangki(Request $req){
        $this->validate($req,
        [
            'email'=>'required|email|unique:users,email',
            'password'=>'required|min:6|max:20',
            'fullname'=>'required',
            're_password'=>'required|same:password',
            'phone'=>'required'
        ],
        [
            'email.required'=>'Vui lòng nhập Email',
            'email.email'=>'Không đúng định dạng Email',
            'email.unique'=>'Email đã tồn tại',
            'password.required'=>'Vui lòng nhập mật khẩu',
            're_password.same'=>'Xác nhận mật khẩu không đúng',
            'password.min'=>'Mật khẩu ít nhất 6 kí tự',
            'password.max'=>'Mật khẩu tối đa 20 kí tự',
            'phone.required'=>'Bạn chưa nhập số điện thoại'
        ]);

        $user = new User();
        $user->full_name = $req->fullname;
        $user->email = $req->email;
        $user->password = Hash::make($req->password);
        $user->phone = $req->phone;
        $user->address = $req->address;
        $user->save();
        return redirect('/dang-nhap')->with('thongbao','Bạn đã đăng kí thành công. Bạn có thể đăng nhập');
    }

    public function postDangxuat(){
        Session::flush();
        Auth::logout();
        return redirect()->route('trang-chu');
    }

    public function getTimkiem(Request $req){
        $product = Product::where('name','like','%'.$req->key.'%')
                            ->orwhere('unit_price',$req->key)
                            ->get();
        return view('page.search',compact('product'));
    }
}

