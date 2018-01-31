<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-10-30
 * Time: 下午1:37
 */

/**
 * 取得系统配置信息
 *
 * @param string $key 取得下标值
 * @return mixed
 */
function C($key){
    if (strpos($key,'.')){
        $key = explode('.',$key);
        $value = $GLOBALS['config'][$key[0]];
        if (isset($key[2])){
            return $value[$key[1]][$key[2]];
        }else{
            return $value[$key[1]];
        }
    }else{
        return $GLOBALS['config'][$key];
    }
}


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


// 记录和统计时间（微秒）
function addUpTime($start,$end='',$dec=3) {
    static $_info = array();
    if(!empty($end)) { // 统计时间
        if(!isset($_info[$end])) {
            $_info[$end]   =  microtime(TRUE);
        }
        return number_format(($_info[$end]-$_info[$start]),$dec);
    }else{ // 记录时间
        $_info[$start]  =  microtime(TRUE);
    }
}


function eh($str){
    echo $str.'<br>';
}




function escape($contetn){
    return mysql_real_escape_string($contetn);
}

function create_pf_product($product_data,$price_rate=0.77,$language_id=1){
    $image_save_dir='products_img/';
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
        $save_path = $image_save_dir;
        if(!is_dir($save_path))
            mkdir($save_path,0777,true);

        $image_name = time().mt_rand(10000,99999);
        for($i=0;$i<count($image);$i++){
            $extension = strtolower(substr(strrchr($image[$i],"."),0));

            //主图片
            if($i == 0){
                $image_save_name = $image_save_dir.$image_name.$extension;
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

    $pro_data = array(
        'master_categories_id'  => $master_categories_id,
        'products_image'        => $image_save_name,
        'products_quantity'     => '2000',
        'products_model'        => $model,
        'products_weight'       => $weight,
        'products_status'       => 1,
        'products_date_added'   => $datetime,
        'products_last_modified'=> date('Y-m-d H:i:s',time()+600),
        'products_price'        => $products_price,
        'products_price_sorter' => $products_price_sorter,

    );
    $product_id = Db::insert(TABLE_PRODUCTS,$pro_data);


    //$db->query("INSERT INTO ".TABLE_PRODUCTS." SET master_categories_id='".$master_categories_id."',products_image='"
        //.escape($image_save_name)."',products_quantity='2000',products_model='$model',products_weight='$weight',products_status='1',products_date_added='".$datetime."',products_last_modified='".date('Y-m-d H:i:s',time()+600)."',
        //products_price='$products_price',products_price_sorter='$products_price_sorter'");

    //$products_id = $db->insert_id;

    $desc_data = array(
        'products_id'       => $product_id,
        'products_name'     => $name,
        'products_description' => $desc,
        'language_id'       => $language_id
    );
    Db::insert(TABLE_PRODUCTS_DESCRIPTION,$desc_data);
    //$db->query("INSERT INTO ".TABLE_PRODUCTS_DESCRIPTION." SET products_id='$products_id',products_name='".escape($name)."',products_description='".escape($desc)."',language_id='".$language_id."'");

    $p_c_data = array(
        'products_id'       => $product_id,
        'categories_id'     => $master_categories_id
    );

    Db::insert(TABLE_PRODUCTS_TO_CATEGORIES,$p_c_data);

    //$db->query("INSERT INTO ".TABLE_PRODUCTS_TO_CATEGORIES." SET products_id='$products_id',categories_id='".$master_categories_id."'");

    if($special){
        $special_data = array(
            'products_id'                    => $product_id,
            'specials_new_products_price'    => $products_price_sorter,
            'specials_date_added'            => $datetime,
            'specials_last_modified'         => $datetime
        );
        Db::insert(TABLE_SPECIALS,$special_data);
        //$db->query("INSERT INTO ".TABLE_SPECIALS." SET products_id='$products_id',specials_new_products_price='$products_price_sorter',specials_date_added='$datetime',specials_last_modified='$datetime'");
    }

    //$this->products_id = $products_id;

    return $product_id;
}

function saveImages($down_path,$image_name,$save_path){
    if($down_path!=''){
        $save_name=$save_path.$image_name;

        if(!is_dir($save_path))
            mkdir($save_path,0777,true);

        $image_content=curlGet($down_path);
        if($image_content){
            file_put_contents($save_name,$image_content);
        }
        clearstatcache();
        sleep(0.5);
    }
}

function getImageName($path){
    $name_arr=explode('/',$path);
    return $name_arr[sizeof($name_arr)-1];
}
function getImageCate($path){
    $name_arr=explode('/',$path);
    return $name_arr[sizeof($name_arr)-2].'/';
}

function options($option_name,$db,$language_id=1){
    $row = $db->get_row("SELECT * FROM ".TABLE_PRODUCTS_OPTIONS." WHERE  products_options_name='".escape($option_name)."' AND language_id='".$language_id."'");
    if(!$row){
        $max_id = $db->get_var("SELECT MAX(products_options_id) FROM ".TABLE_PRODUCTS_OPTIONS." WHERE language_id='".$language_id."' LIMIT 1");
        $products_options_id = $max_id + 1;
        $db->query("INSERT INTO ".TABLE_PRODUCTS_OPTIONS." SET products_options_id='".$products_options_id."', products_options_name='".escape($option_name)."',language_id='".$language_id."'");
        //$products_options_id = $products_options_id;
    }else{
        $products_options_id = $row->products_options_id;
    }

    return $products_options_id;
}

function options_values($option_value_name,$db,$language_id){
    $row = $db->get_row("SELECT * FROM ".TABLE_PRODUCTS_OPTIONS_VALUES." WHERE products_options_values_name='".escape($option_value_name)."' AND language_id='".$language_id."'");

    if(!$row){
        $max_id = $db->get_var("SELECT MAX(products_options_values_id) FROM ".TABLE_PRODUCTS_OPTIONS_VALUES." WHERE language_id='".$language_id."' LIMIT 1");
        $products_options_values_id = $max_id + 1;
        $db->query("INSERT INTO ".TABLE_PRODUCTS_OPTIONS_VALUES." SET products_options_values_id='".$products_options_values_id."',products_options_values_name='".escape($option_value_name)."',language_id='".$language_id."'");
        //$this->products_options_values_id = $products_options_values_id;
    }else{
        $products_options_values_id = $row->products_options_values_id;
    }

    return $products_options_values_id;
}

function values_to_options($options_id,$options_values_id,$db,$language_id){
    $row = $db->get_row("SELECT * FROM ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." WHERE products_options_id='".$options_id."' AND products_options_values_id='".$options_values_id."' LIMIT 1");
    if(!$row){
        $db->query("INSERT INTO ".TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS." SET products_options_id='".$options_id."', products_options_values_id='".$options_values_id."'");

        $products_options_values_to_products_options_id = $db->insert_id;
    }else{
        $products_options_values_to_products_options_id = $row->products_options_values_to_products_options_id;
    }

    return $products_options_values_to_products_options_id;
}

function attributes($products_id,$options_id,$options_values_id,$db,$language_id,$sort=0,$display_only=0){
    $row = $db->get_row("SELECT * FROM ".TABLE_PRODUCTS_ATTRIBUTES." WHERE products_id='".$products_id."' AND options_id='".$options_id."' AND options_values_id='".$options_values_id."' LIMIT 1");

    if(!$row){
        $db->get_row("INSERT INTO ".TABLE_PRODUCTS_ATTRIBUTES." SET products_id='".$products_id."', options_id='".$options_id."', options_values_id='".$options_values_id."',products_options_sort_order='".$sort."',attributes_display_only='$display_only'");
        $products_attributes_id = $db->insert_id;
    }else{
        $products_attributes_id = $row->products_attributes_id;
    }

    return $products_attributes_id;
}

function products_to_categories($pid,$cid,$db){
    $db->query("REPLACE INTO ".TABLE_PRODUCTS_TO_CATEGORIES." SET products_id='".$pid."',categories_id='".intval($cid)."'");
}


function add_caiji_record($cid,$c_url,$p_url,$pid){
    $datetime = date('Y-m-d H:i:s');
    $data = array(
        'product_url'       => $p_url,
        'cid'               => $cid,
        'category_url'      => $c_url,
        'pid'               => $pid,
        'add_date'          => $datetime
    );
    $result = Db::insert(TABLE_PRODUCTS_COPY_RECORDS,$data);
    //$db->query("INSERT INTO ".TABLE_PRODUCTS_COPY_RECORDS." SET product_url='".escape($p_url)."',cid='$cid',category_url='".escape($c_url)."',pid='$pid',add_date='$datetime'");
    return $result;
}

function has_caiji_record($p_url){
    //Db::getRow();
    $count = Db::getCount(TABLE_PRODUCTS_COPY_RECORDS,array('product_url'=>trim($p_url)));
    if($count > 0){
        return true;
    }
    return false;

    $row = $db->get_row("SELECT * FROM ".TABLE_PRODUCTS_COPY_RECORDS." WHERE product_url='".escape($p_url)."' LIMIT 1");
    if(!$row || $row->pid < 1){
        return false;
    }
    return true;
}

//创建分类
function create_category($name,$parent_id=0,$language_id=1){
    $datetime = date('Y-m-d H:i:s');
    $result = Db::query("SELECT c.categories_id FROM ".TABLE_CATEGORIES." c,".TABLE_CATEGORIES_DESCRIPTION." cd WHERE c.categories_id=cd.categories_id AND cd.categories_name='".$name."' LIMIT 1");
    $result = mysqli_fetch_array($result,MYSQLI_ASSOC);

    //$result = $db->get_row("SELECT c.categories_id FROM ".TABLE_CATEGORIES." c,".TABLE_CATEGORIES_DESCRIPTION." cd WHERE c.categories_id=cd.categories_id AND cd.categories_name='".escape($name)."' AND c.parent_id='$parent_id' LIMIT 1");
    //$result = $db->get_row("SELECT c.categories_id FROM ".TABLE_CATEGORIES." c,".TABLE_CATEGORIES_DESCRIPTION." cd WHERE c.categories_id=cd.categories_id AND cd.categories_name='".escape($name)."' LIMIT 1");

    if($result['categories_id'] < 1){
        $categories_id = Db::insert(TABLE_CATEGORIES,array('parent_id'=>$parent_id,'date_added'=>$datetime));
        //Db::query("INSERT INTO ".TABLE_CATEGORIES." SET parent_id='$parent_id',date_added='".$datetime."'");
        //$categories_id = $db->insert_id;
        Db::insert(TABLE_CATEGORIES_DESCRIPTION,array('categories_id'=>$categories_id,'language_id'=>$language_id,'categories_name'=>$name));
        //Db::query("INSERT INTO ".TABLE_CATEGORIES_DESCRIPTION." SET categories_id='".$categories_id."',language_id='".$language_id."',categories_name='".escape($name)."'");
    }else{
        $categories_id = $result['categories_id'];
    }

    return $categories_id;
}