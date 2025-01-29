<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorecoinremitterCoinRequest;
use App\Http\Requests\UpdatecoinremitterCoinRequest;
use App\Models\coinremitterCoin;
use App\Models\Coinremitter;
use Illuminate\Http\Request;
use App\Models\coinremitterWallet;
use App\Models\CoinSettings;
class CoinremitterCoinController extends Controller
{
    /**
     * Display a listing of the resource.
     *Coinremitter
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware("custom_permission:Coinremitter|root");
    }
    public function index()
    {
        $CoinSettings = CoinSettings::where('company_id',auth()->user()->company_id)->get();
        $credential =[];
        foreach($CoinSettings as $CoinSettingsData){
            $credential[$CoinSettingsData->code]=['API_KEY'=>$CoinSettingsData->api_key,'PASSWORD'=>$CoinSettingsData->password];
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'coin',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'credentials' => $credential,
            'rate'=>Coinremitter::where('company_id',auth()->user()->company_id)->first(),
            'coins'=>coinremitterCoin::where(['company_id'=>auth()->user()->company_id])->get(),
        ];
        return view('company.coinremitter.addcoin')->with($data);
    }
    public function settings()
    {
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'coin_settings',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'coins'=>CoinSettings::where('company_id',auth()->user()->company_id)->get(),
        ];
        return view('company.coinremitter.coin_settings')->with($data);
    }
    public function saveSetting(Request $request){
        if(CoinSettings::where(['company_id'=>auth()->user()->company_id,'code'=>$request->code])->count()==0){
            $CoinSettings =new CoinSettings; //::where(['company_id'=>auth()->user()->company_id,'code'=>$request->code])->frist();
            $CoinSettings->company_id =auth()->user()->company_id;
            $CoinSettings->created_by=auth()->user()->id;
            $CoinSettings->code = $request->code;
            $CoinSettings->api_key= $request->api_key;
            $CoinSettings->password= $request->password;
            $CoinSettings->status_id= $request->status_id;
            $CoinSettings->save();
            return redirect()->back()->with('success', 'Coin Set!');
            // return response()->json(["status" => 1, "message" => "Coin Set!"]);
        }else{
            // return response()->json(["status" => 0, "message" => "Coin Already Set!"]);
            return redirect()->back()->with('fail', 'Coin already Set!');
        }
    }
    public function updateSetting(Request $request){
        $coindata =CoinSettings::where(['company_id'=>auth()->user()->company_id,'id'=>$request->coin_ID])->first();
        if($coindata){
            $CoinSettings =$coindata; //::where(['company_id'=>auth()->user()->company_id,'code'=>$request->code])->frist();
            $CoinSettings->created_by=auth()->user()->id;
            $CoinSettings->code = $request->code;
            $CoinSettings->api_key= $request->api_key;
            $CoinSettings->password= $request->password;
            $CoinSettings->status_id= $request->status_id;
            $CoinSettings->save();
            return redirect()->back()->with('success', 'Coin Set!');
            // return response()->json(["status" => 1, "message" => "Coin Set!"]);
        }else{
            return redirect()->back()->with('fail', 'Coin update failed!');
            // return response()->json(["status" => 0, "message" => "Coin Already Set!"]);
        }
    }
    public function editSetting($id){
        $coindata =CoinSettings::where(['company_id'=>auth()->user()->company_id,'id'=>$id])->first();
        return $coindata;
    }
    public function destroySetting($id){
        $coindata =CoinSettings::where(['company_id'=>auth()->user()->company_id,'id'=>$id])->delete();
        return redirect()->back()->with('success', 'Coin Deleted!');
            // return response()->json(["status" => 1, "message" => "Coin deleted!"]);
        // return $coindata;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function storeRate(Request $request)
    {
        try{
        $Coinremitter = Coinremitter::where('company_id',auth()->user()->company_id)->first();
        if($Coinremitter){
            $Coinremitter->naira_to_USD = $request->rate;
            $Coinremitter->buy_rate = $request->buy_rate;
            $Coinremitter->save();
        }else{
            $Coinremitter =new Coinremitter;
            $Coinremitter->company_id = auth()->user()->company_id;
            $Coinremitter->naira_to_USD = $request->rate;
            $Coinremitter->buy_rate = $request->buy_rate;
            $Coinremitter->save();
        }
        return response()->json(['status' => 1, 'message' => 'Rate Set!']);
    }catch(Exception $e) {
        $message = 'Message: ' .$e->getMessage().$e->getLine();
        return response()->json(["status" => 0, "message" => "$message!"]);
    }

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorecoinremitterCoinRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(coinremitterCoin::where(['code'=>$request->code,'company_id'=>auth()->user()->company_id])->count() > 0){
           // return \response()->json(['status'=>false,'message'=>'Coin Already Exist!'], 400);
           return \Redirect::back()->withErrors(['msg' => 'Coin Already Exist!']);
        }//118453
        $coinremitterCoin = new coinremitterCoin;
        $coinremitterCoin->company_id = auth()->user()->company_id;
        $coinremitterCoin->name = $request->name;
        $coinremitterCoin->code = strtoupper($request->code);
        $coinremitterCoin->rate = $request->rate;
        $coinremitterCoin->buy_rate = $request->buy_rate;
        $coinremitterCoin->icon_link = $request->icon_link;
        $coinremitterCoin->save();
        //return \response()->json(['status'=>true,'message'=>'Coin Add!'], 400);
        return redirect()->back()->with('success', 'Coin Added!');
    }
    public function storeUpdate(Request $request)
    {
        $coin = coinremitterCoin::where(['id'=>$request->coin_ID,'company_id'=>auth()->user()->company_id])->first();
        if($coin){
            $coinremitterCoin = $coin;
            $coinremitterCoin->name = $request->name;
            $coinremitterCoin->code = strtoupper($request->code);
            $coinremitterCoin->rate = $request->rate;
            $coinremitterCoin->status_id = $request->status_id;
            $coinremitterCoin->buy_rate = $request->buy_rate;
            $coinremitterCoin->icon_link = $request->icon_link;
            $coinremitterCoin->save();
            return redirect()->back()->with('success', 'Coin Updated!');
        }else{
           return Redirect::back()->withErrors(['msg' => 'Coin Doesn\'t Exist!']);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\coinremitterCoin  $coinremitterCoin
     * @return \Illuminate\Http\Response
     */
    public function show(coinremitterCoin $coinremitterCoin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\coinremitterCoin  $coinremitterCoin
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $coinData = coinremitterCoin::where(['id'=>$id,'company_id'=>auth()->user()->company_id])->first();
        return json_encode($coinData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatecoinremitterCoinRequest  $request
     * @param  \App\Models\coinremitterCoin  $coinremitterCoin
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if(coinremitterCoin::where(['code'=>$request->coin,'company_id'=>auth()->user()->company_id])->count() > 0){
            return \Redirect::back()->withErrors(['msg' => 'Coin Already Exist!']);
         }
         $coinremitterCoin =  coinremitterCoin::where('id',$request->crypto_id)->first();
         $coinremitterCoin->company_id = auth()->user()->company_id;
         $coinremitterCoin->name = $request->name;
         $coinremitterCoin->code = $request->code;
         $coinremitterCoin->status_id = $request->status_id;
         $coinremitterCoin->rate = $request->rate;
        $coinremitterCoin->buy_rate = $request->buy_rate;
         $coinremitterCoin->icon_link = $request->icon_link;
         $coinremitterCoin->save();
         return redirect()->back()->with('success', 'Coin Added!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\coinremitterCoin  $coinremitterCoin
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(coinremitterWallet::where('coinremitter_coin_id',$id)->count() == 0){
            $coinremitterCoin =  coinremitterCoin::where('id',$id)->delete();
            return response()->json(['status' => 1, 'message' => 'Coin Deleted!']);
        }else{
            return response()->json(['status' => 0, 'message' => 'Active User Wallet wont preventing deleting this account!']);
        }
    }
}
