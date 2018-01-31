<?php
/**
 * Created by JetBrains PhpStorm.
 * User: liang
 * Date: 13-7-10
 * Time: 上午10:15
 * To change this template use File | Settings | File Templates.
 */

define('TABLE_CATEGORIES','categories');
define('TABLE_CATEGORIES_DESCRIPTION','categories_description');
define('TABLE_PRODUCTS','products');
define('TABLE_PRODUCTS_DESCRIPTION','products_description');
define('TABLE_PRODUCTS_TO_CATEGORIES','products_to_categories');
define('TABLE_SPECIALS','specials');
define('TABLE_PRODUCTS_ATTRIBUTES','products_attributes');
define('TABLE_PRODUCTS_OPTIONS','products_options');
define('TABLE_PRODUCTS_OPTIONS_VALUES','products_options_values');
define('TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS','products_options_values_to_products_options');
define('TABLE_PRODUCTS_COPY_RECORDS','products_copy_records');
define('TABLE_REVIEWS','reviews');

function curlGet($url){
    $ch = curl_init();
    curl_setopt ($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_ENCODING,'gzip');
    curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");
    curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "cookiefile");
    curl_setopt($ch, CURLOPT_COOKIEJAR, "cookiefile");
    curl_setopt($ch, CURLOPT_TIMEOUT, "10");
    $content = curl_exec($ch);
    if(substr(strrchr($url,"."),1) == 'shtml') {
        $code = curl_getinfo($ch);
        echo '<br>' . $code['http_code'] . '<br>';
    }
    if(curl_errno($ch)){
        return '';
    }
    curl_close ($ch);
    return $content;
}

function getReviewsPath($p_id,$page){
    $base_url='http://cdn.powerreviews.com/repos/16552/pr/pwr/content/';
    $language='-en_US-';
    $back_url='-reviews.js';

    $str=$p_id;
    $CG=0;
    $CF='';
    for($CF=0;$CF<strlen($str);$CF++){
        $CE=ord($str[$CF]);
        $CE=$CE * abs(255-intval($CE));
        $CG+=intval($CE);
    }
    $CG = $CG % 1023;
    $CG = $CG + "";
    $CI = 4;
    $CD= str_split($CG);
    for($CF=0;$CF < $CI- strlen($CG);$CF++){
        array_unshift($CD, '0');
    }
    $CG=implode('', $CD);
    $CG=substr($CG,0,$CI/2).'/'.substr($CG,$CI/2,$CI);

    $path=$base_url.$CG.'/'.$p_id.$language.$page.$back_url;

    //return $CG;
    return $path;
}

function eh($str){
    echo $str.'<br>';
}

class Models {

    private $db = null;
    private $image_save_dir = 'products_img/';
    private $language_id = 1;

    public $categories_id = 0;
    public $products_id = 0;
    public $products_options_id = 0;
    public $products_options_values_id = 0;
    public $products_options_values_to_products_options_id = 0;
    public $products_attributes_id = 0;

    public function Model($db,$language_id=1,$image_save_dir=''){
        $this->db = $db;
        if($language_id != 1){
            $this->language_id = $language_id;
        }
        if($image_save_dir != ''){
            $this->image_save_dir = $image_save_dir;
        }
    }

    public function escape($content){
        return mysql_real_escape_string($content);
    }

    //创建分类
    function create_category($name,$parent_id=0){
        $datetime = date('Y-m-d H:i:s');

        $result = $this->db->get_row("SELECT c.categories_id FROM ".TABLE_CATEGORIES." c,".TABLE_CATEGORIES_DESCRIPTION." cd WHERE c.categories_id=cd.categories_id AND cd.categories_name='".$this->escape($name)."' AND c.parent_id='$parent_id' LIMIT 1");

        if($result->categories_id < 1){
            $this->db->query("INSERT INTO ".TABLE_CATEGORIES." SET parent_id='$parent_id',date_added='".$datetime."'");
            $this->categories_id = $this->db->insert_id;
            $this->db->query("INSERT INTO ".TABLE_CATEGORIES_DESCRIPTION." SET categories_id='".$this->categories_id."',language_id='".$this->language_id."',categories_name='".$this->escape($name)."'");
        }else{
            $this->categories_id = $result->categories_id;
        }

        return $this->categories_id;
    }

    public function create_multi_category($cnames=array()){
        $top_cid = 0;
        if(count($cnames)){
            foreach($cnames as $cname){
                $top_cid = $this->create_category($cname,$top_cid);
                echo "Build Category: $cname: $top_cid, ";
            }
        }

        return $top_cid;
    }

    public function reset_category_id(){
        $this->categories_id = 0;
    }

    public function reset_product(){
        $this->products_id = 0;
        $this->products_options_id = 0;
        $this->products_options_values_id = 0;
        $this->products_options_values_to_products_options_id = 0;
        $this->products_attributes_id = 0;
    }

    //创建产品
    //$name,$desc,$base_price,$special,$image=array(),$price_rate是货币汇率，默认为欧元，如果是美元，请指定为0.77
    function create_pf_product($product_data,$price_rate=0.77){
        $datetime = date('Y-m-d H:i:s');
        extract($product_data);

        $products_price = $base_price * $price_rate;
        if(!$special){
            $products_price_sorter = $base_price;
        }else{
            $products_price_sorter = $special;
        }
        $products_price_sorter = $products_price_sorter * $price_rate;

        if(!$weight) $weight = '0.0';

        //保存图片
        $image_save_name = '';
        if(count($image) > 0){
            $save_path = $this->image_save_dir;
            if(!is_dir($save_path))
                mkdir($save_path,0777,true);

            $image_name = time().mt_rand(10000,99999);
            for($i=0;$i<count($image);$i++){
                $extension = strtolower(substr(strrchr($image[$i],"."),0));

                //主图片
                if($i == 0){
                    $image_save_name = $this->image_save_dir.$image_name.$extension;
                    $image_name_new = $image_name.$extension;
                }else{
                    //$image_save_name = $image_save_dir.$image_name.'_'.$i.$extension;
                    $image_name_new = $image_name.'_'.$i.$extension;
                }

                $save_image = $save_path.$image_name_new;
                $image_content = curlGet($image[$i]);
                if($image_content) {
                    file_put_contents($save_image, $image_content);
                }
                clearstatcache();
                sleep(0.5);
            }
        }

        $this->db->query("INSERT INTO ".TABLE_PRODUCTS." SET master_categories_id='".$master_categories_id."',products_image='".$this->escape($image_save_name)."',products_quantity='2000',products_model='$model',products_weight='$weight',products_status='1',products_date_added='".$datetime."',products_last_modified='".date('Y-m-d H:i:s',time()+600)."',products_price='$products_price',products_price_sorter='$products_price_sorter'");

        $products_id = $this->db->insert_id;

        $this->db->query("INSERT INTO ".TABLE_PRODUCTS_DESCRIPTION." SET products_id='$products_id',products_name='".$this->escape($name)."',products_description='".$this->escape($desc)."',language_id='".$this->language_id."'");

        $this->db->query("INSERT INTO ".TABLE_PRODUCTS_TO_CATEGORIES." SET products_id='$products_id',categories_id='".$master_categories_id."'");

        if($special){
            $this->db->query("INSERT INTO ".TABLE_SPECIALS." SET products_id='$products_id',specials_new_products_price='$products_price_sorter',specials_date_added='$datetime',specials_last_modified='$datetime'");
        }

        $this->products_id = $products_id;

        return $products_id;
    }

    //创建产品
    //$name,$desc,$base_price,$special,$image=array(),$price_rate是货币汇率，默认为欧元，如果是美元，请指定为0.77
    function create_product($product_data,$price_rate=0.77){
        $datetime = date('Y-m-d H:i:s');
        extract($product_data);

        $products_price = $base_price * $price_rate;
        if(!$special){
            $products_price_sorter = $base_price;
        }else{
            $products_price_sorter = $special;
        }
        $products_price_sorter = $products_price_sorter * $price_rate;

        if(!$weight) $weight = '0.0';

        //保存图片
        $image_save_name = '';
        if(count($image) > 0){
            $save_path = $this->image_save_dir;
            if(!is_dir($save_path))
                mkdir($save_path,0777,true);

            $image_name = time().mt_rand(10000,99999);
            for($i=0;$i<count($image);$i++){
                $extension = strtolower(substr(strrchr($image[$i],"."),0));

                //主图片
                if($i == 0){
                    $image_save_name = $this->image_save_dir.$image_name.$extension;
                    $image_name_new = $image_name.$extension;
                }else{
                    //$image_save_name = $image_save_dir.$image_name.'_'.$i.$extension;
                    $image_name_new = $image_name.'_'.$i.$extension;
                }

                $save_image = $save_path.$image_name_new;
                $image_content = curlGet($image[$i]);
                if($image_content) {
                    file_put_contents($save_image, $image_content);
                }
                clearstatcache();
                sleep(0.5);
            }
        }

        $this->db->query("INSERT INTO ".TABLE_PRODUCTS." SET master_categories_id='".$master_categories_id."',products_image='".$this->escape($image_save_name)."',products_quantity='2000',products_model='$model',products_weight='$weight',products_status='1',products_date_added='".$datetime."',products_last_modified='".date('Y-m-d H:i:s',time()+600)."',products_price='$products_price',products_price_sorter='$products_price_sorter'");

        $products_id = $this->db->insert_id;

        $this->db->query("INSERT INTO ".TABLE_PRODUCTS_DESCRIPTION." SET products_id='$products_id',products_name='".$this->escape($name)."',products_description='".$this->escape($desc)."',language_id='".$this->language_id."'");

        $this->db->query("INSERT INTO ".TABLE_PRODUCTS_TO_CATEGORIES." SET products_id='$products_id',categories_id='".$master_categories_id."'");

        if($special){
            $this->db->query("INSERT INTO ".TABLE_SPECIALS." SET products_id='$products_id',specials_new_products_price='$products_price_sorter',specials_date_added='$datetime',specials_last_modified='$datetime'");
        }

        $this->products_id = $products_id;

        return $products_id;
    }

    public function products_to_categories($pid,$cid){
        $this->db->query("REPLACE INTO ".TABLE_PRODUCTS_TO_CATEGORIES." SET products_id='".$pid."',categories_id='".intval($cid)."'");
    }

    public function options($option_name){
        $row = $this->db->get_row("SELECT * FROM ".TABLE_PRODUCTS_OPTIONS." WHERE  products_options_name='".$this->escape($option_name)."' AND language_id='".$this->language_id."'");
        if(!$row){
            $max_id = $this->db->get_var("SELECT MAX(products_options_id) FROM ".TABLE_PRODUCTS_OPTIONS." WHERE language_id='".$this->language_id."' LIMIT 1");
            $products_options_id = $max_id + 1;
            $this->db->query("INSERT INTO ".TABLE_PRODUCTS_OPTIONS." SET products_options_id='".$products_options_id."', products_options_name='".$this->escape($option_name)."',language_id='".$this->language_id."'");
            $this->products_options_id = $products_options_id;
        }else{
            $this->products_options_id = $row->products_options_id;
        }

        return $this->products_options_id;
    }

    public function options_values($option_value_name){
        $row = $this->db->get_row("SELECT * FROM ".TABLE_PRODUCTS_OPTIONS_VALUES." WHERE products_options_values_name='".$this->escape($option_value_name)."' AND language_id='".$this->language_id."'");

        if(!$row){
            $max_id = $this->db->get_var("SELECT MAX(products_options_values_id) FROM ".TABLE_PRODUCTS_OPTIONS_VALUES." WHERE language_id='".$this->language_id."' LIMIT 1");
            $products_options_values_id = $max_id + 1;
            $this->db->query("INSERT INTO ".TABLE_PRODUCTS_OPTIONS_VALUES." SET products_options_values_id='".$products_options_values_id."',products_options_values_name='".$this->escape($option_value_name)."',language_id='".$this->language_id."'");
            $this->products_options_values_id = $products_options_values_id;
        }else{
            $this->products_options_values_id = $row->products_options_values_id;
        }

        return $this->products_options_values_id;
    }

    public function values_to_options($options_id,$options_values_id){
        $row = $this->db->get_row("SELECT * FROM ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." WHERE products_options_id='".$options_id."' AND products_options_values_id='".$options_values_id."' LIMIT 1");
        if(!$row){
            $this->db->query("INSERT INTO ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." SET products_options_id='".$options_id."', products_options_values_id='".$options_values_id."'");

            $this->products_options_values_to_products_options_id = $this->db->insert_id;
        }else{
            $this->products_options_values_to_products_options_id = $row->products_options_values_to_products_options_id;
        }

        return $this->products_options_values_to_products_options_id;
    }

    public function attributes($products_id,$options_id,$options_values_id,$sort=0,$display_only=0){
        $row = $this->db->get_row("SELECT * FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id='".$products_id."' AND options_id='".$options_id."' AND options_values_id='".$options_values_id."' LIMIT 1");

        if(!$row){
            $this->db->get_row("INSERT INTO ".TABLE_PRODUCTS_ATTRIBUTES." SET products_id='".$products_id."', options_id='".$options_id."', options_values_id='".$options_values_id."',products_options_sort_order='".$sort."',attributes_display_only='$display_only'");
            $this->products_attributes_id = $this->db->insert_id;
        }else{
            $this->products_attributes_id = $row->products_attributes_id;
        }

        return $this->products_attributes_id;
    }

    public function has_caiji_record($p_url){
        $row = $this->db->get_row("SELECT * FROM ".TABLE_PRODUCTS_COPY_RECORDS." WHERE product_url='".$this->escape($p_url)."' LIMIT 1");
        if(!$row || $row->pid < 1){
            return false;
        }
        return true;
    }

    public function add_caiji_record($cid,$c_url,$p_url,$pid){
        $datetime = date('Y-m-d H:i:s');
        $this->db->query("INSERT INTO ".TABLE_PRODUCTS_COPY_RECORDS." SET product_url='".$this->escape($p_url)."',cid='$cid',category_url='".$this->escape($c_url)."',pid='$pid',add_date='$datetime'");
        return $this->db->insert_id;
    }

    public function disable_img_empty_products($img){
        $this->db->query("UPDATE ".TABLE_PRODUCTS." SET products_status='0' WHERE products_image='".$this->escape($img)."' LIMIT 1");
    }

    public function disable_name_empty_products(){
        $this->db->query("UPDATE products SET products_status='0' where products_id in (SELECT `products_id` FROM `products_description` WHERE `products_name`='')");
    }

    public function add_product_reviews($reviews_data,$product_id,$language_id=1){
        if(count($reviews_data)>0 && $product_id>0){
           // $datetime = date('Y-m-d H:i:s');
            foreach($reviews_data as $r){
                $this->db->query('INSERT INTO '.TABLE_REVIEWS.'(product_id,language_id,record_id,level,user_name,title,content,from_country,review_time,created)
                    Values('.$product_id.','.$language_id.',"'.$r["id"].'",'.$r["level"].',"'.$r["user"].'","'.$r["title"].'","'.$r["content"].'","'.$r["country"].'","'.$r["time"].'",now())');
            }
        }


    }
}