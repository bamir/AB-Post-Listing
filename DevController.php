<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\CfOrdersData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Models\ShopifyProducts;
use App\Models\ProductVariants;
use App\Models\MatchedProducts;
use App\Models\ProductBundles;
use App\Models\BundledSavedProducts;
use App\Models\MatchedBundles;
use App\Models\ClickFunnelsDetails;
use App\Models\CfCustomers;
use App\Models\MatchedSubscriptions;
use App\Models\StripeSubscriptions;
use Mail;
use App\Mail\UnMatchedOrderDetails;

class CFOrdersController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function test()
    {
        $clickfunnels = CfOrdersData::selectRaw('
        MAX(id) AS id,
        email,
        shopify_order_id,
        shopify_order_name,
        GROUP_CONCAT(productname SEPARATOR "|") AS productname,
        SUM(productprice) AS productprice,
        MAX(status) AS status,
        GROUP_CONCAT(DISTINCT first_name) AS first_name,
        GROUP_CONCAT(DISTINCT last_name) AS last_name,
        GROUP_CONCAT(DISTINCT source) AS source,
        MAX(submitted) AS submitted,
        MAX(updated_at) AS updated_at
    ')
    ->groupBy('email','shopify_order_id','shopify_order_name')
    ->havingRaw('COUNT(email) > 1')
    ->orderBy('updated_at', 'desc')->paginate(100);
        //$clickfunnels = CfOrdersData::All()->sortByDesc("updated_at");
       //$clickfunnels = CfOrdersData::orderBy('updated_at', 'desc')->paginate(100);
       return view('pages.orders_list', compact('clickfunnels'));
    }
    /**
     * Display a listing of the unsent orders.
     *
     * @return \Illuminate\Http\Response
     */
    public function unsentOrders()
    {
        /*$clickfunnels = CfOrdersData::where('submitted', '=', "no")
                                    ->orderBy('updated_at', 'desc')->paginate(100);*/
        \DB::raw("SET SESSION group_concat_max_len = 260000");
        $clickfunnels = CfOrdersData::selectRaw('
        MAX(id) AS id,
        email,
        GROUP_CONCAT(productname SEPARATOR "|") AS productname,
        GROUP_CONCAT(shopify_errors SEPARATOR "|") AS shopify_errors,
        SUM(productprice) AS productprice,
        MAX(status) AS status,
        GROUP_CONCAT(DISTINCT first_name) AS first_name,
        GROUP_CONCAT(DISTINCT last_name) AS last_name,
        GROUP_CONCAT(DISTINCT source) AS source,
        MAX(submitted) AS submitted,
        MAX(updated_at) AS updated_at
    ')
    ->groupBy('email')
    ->where('submitted', '=', "no")
    ->havingRaw('COUNT(email) > 1')
    ->orderBy('updated_at', 'desc')->paginate(100);
       return view('pages.unsent_orders_list', compact('clickfunnels'));
    }
    /**
         * Display a listing of the resource.
         *
         * @return \Illuminate\Http\Response
         */
    public function searchorder(Request $request)
        {
            $clickfunnels = CfOrdersData::query()
   ->where('email', 'LIKE', "%{$request->input('searchquery')}%") 
   ->orderBy('updated_at', 'desc')->paginate(100);
            //$clickfunnels = CfOrdersData::orderBy('updated_at', 'desc')->paginate(100);
           return view('pages.orders_list', compact('clickfunnels'));
        }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function postCategories()
    {
        echo '<pre>';
        $websites = Website::All();
        foreach ($websites as $website)
        {
            if($website->status == 'enabled'){
            $categoryData = Category::All();
            //$categoryName .= $categoryData['category_name'];
            foreach($categoryData as $categoryNames)
            {
                $string = str_replace("&","",strtolower($categoryNames->category_name));
                $newstring = preg_replace('/\s+/', '-', $string);
                echo '<br>';
                $url = $website->website.'/wp-json/wp/v2/categories';
                $data = array(
                         "name" => $categoryNames->category_name,
                         "slug" => $newstring
                         );
                         $response = Http::withBasicAuth($website->username, $website->password)->post($url, $data);
                         print_r($response->object());
            }
            }
        }
        echo '</pre>';
    }
 
    public function saveMedia($mediaURL,$websiteLink,$username,$password,$imageName)
    {
                     $file_headers = get_headers($mediaURL, true);
                     if(strpos($file_headers[0], '404') == false){
                         $imgfile = file_get_contents( $mediaURL );
                     }
                     
                     $url = $websiteLink.'/wp-json/wp/v2/media/';
                     $headers =array(
                         'content-disposition: attachment; filename='.$imageName,
                         "content-type: image/jpg",
                         "cache-control: no-cache",
                         'Authorization: Basic '.base64_encode( $username . ':' . $password )
                       );
                       
                     $ch = curl_init($url);
                     curl_setopt($ch, CURLOPT_POSTFIELDS, $imgfile);
                     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                     
                     $resultimgID = json_decode(curl_exec($ch));
                     
                     curl_close($ch);
                     //Log::channel('multisiteposterlogs')->info($resultimgID);
                     if(isset($resultimgID->code))
                     {
                         return false;
                     }
                     else if (isset($resultimgID->id))
                     {
                         return $resultimgID->id;
                     }
                     else
                     {
                         return '';
                     }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
   }

}
