<html>
    <head>
        <meta http-equiv="Content-Type" content="text-html; charset=utf-8">
    </head>
</html>
<?php
header("Content-Type: text/html; charset='utf-8'");
set_time_limit(0);
ignore_user_abort(false);
//ini_set('display_errors',1);
error_reporting(E_ALL ^ E_NOTICE);
define('IS_DEBUG',false);

//图片保存路径
$save_image_path='images/desc/';

//定义语种
// en => 1, nl => 3, it => 4, de => 5, fr => 6
$language_id = 1;

//include_once "ezSQL/ez_sql_core.php";
//include_once "ezSQL/ez_sql_mysql.php";
include "core/framework/libraries/log.php";
include "config.ini.php";
global $config;
echo '<pre>';
//print_r($GLOBALS);exit;
include "function.php";
include "db/mysqli.php";

define('DBPRE',($config['db']['master']['dbname']).'`.`'.($config['tablepre']));
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
//include_once "model.php";

if(!IS_DEBUG){
    //$db = new ezSQL_mysql('newbalance','j3CWTJB7NATyWpRL','newbalance','localhost','utf8');
    //$db = new ezSQL_mysql('col_sofa','PjFdvsdyBu0iIBfj','col_sofa','localhost','utf8');
    //$model = new Model($db,$language_id);
}


$domain = 'meaningmart.com';
$base_url='http://meaningmart.com';
$url_pre = 'www.meaningmart.com';
$category_url_pre = '';
$page_var = 'page';    //分页参数符

//echo "<pre>";
//$cate_arr = array('parent_id'=>0,'date_added'=>date('Y-m-d H:i:s'));
//$res= DB::insert(TABLE_CATEGORIES,$cate_arr);
//var_dump($res);


$cate_arr = array(
        1 => 'OTHER TOOLS',
        2 => 'POWER TOOL COMBO KITSS',
        3 => 'LAWN MOWERS',
        4 => 'CHAINSAWS',
);

exit;



$product_all_url='http://meaningmart.com/index.php?main_page=products_all';
$p_all_cont = curlGet($product_all_url);
sleep(0.5);
if($p_all_cont){
    //page count
    $page_count = 1;
    if(preg_match('/Displaying <strong>1<\/strong> to <strong>(.*)<\/strong> \(of <strong>(.*)<\/strong>/Ui',$p_all_cont,$page_t)){
        $page_amount  = $page_t[2];
        $page_member = $page_t[1];
        $page_count  = ceil(intval($page_amount)/intval($page_member));
        //var_dump($page_t);
    }
    eh("总页数{$page_count}");

    for($p=1;$p<=$page_count;$p++){
        eh("第{$p}页");
        $pro_list_url = $product_all_url.'&disp_order=1&page='.$p;
        eh("分类页：{$pro_list_url}");

        $pro_list_cont = curlGet($pro_list_url);
        sleep(0.5);
        if($pro_list_cont){
            if(preg_match_all('/<a href="(.*)"><img/Ui',$pro_list_cont,$link_c)){
                $links_list = $link_c[1];
                if(!empty($links_list)){
                    foreach ($links_list as $link_url){
                        $link_url = str_replace('&amp;','&',$link_url);

                        $check=has_caiji_record($link_url);
                            if(!$check){

                                $cate_name = '';
                                $cate_id   = 0;
                                if(preg_match('/cPath=(.*)\&products_id/Ui',$link_url,$c_t)){
                                    $cate_name = $cate_arr[$c_t[1]];
                                }
                                $cate_id = create_category(trim($cate_name),$cate_id,$language_id);

                                eh("商品分类ID:{$cate_id} 分类名称：{$cate_name}");

                                $p_cont = curlGet(trim($link_url));
                                sleep(0.5);
                                eh("商品地址：{$link_url}");
                                if($p_cont){
                                    //echo htmlspecialchars($p_cont);
                                    //商品标题
                                    $product_title = '';
                                    if(preg_match('/<h1 id="productName" class="productGeneral">(.*)<\/h1>/Ui',$p_cont,$p_t)){
                                        $product_title = $p_t[1];
                                    }
                                    eh("商品标题：{$product_title}");


                                    $pro_model = '';
                                    if(preg_match('/<p>SKU:(.*)<\/p>/Ui',$p_cont,$p_m)){
                                        $pro_model = trim($p_m[1]);
                                    }
                                    eh("商品Model:{$pro_model}");

                                    $pro_price = 0.00;
                                    if(preg_match('/<span class="normalprice">(.*)<\/span>/Ui',$p_cont,$p_price)){
                                        $pro_price = trim(trim($p_price[1]),'$');
                                    }
                                    eh("商品价格：{$pro_price}");

                                    $special_price  = 0.00;
                                    if(preg_match('/<span class="productSpecialPrice">(.*)<\/span>/Ui',$p_cont,$p_special)){
                                        $special_price =  trim(trim($p_special[1]),'$');
                                    }
                                    eh("特价：{$special_price}");

                                    //产品图
                                    $pro_images=array();
                                    if(preg_match('/<img src="(.*)".*id="jqzoomimg"/Ui',$p_cont,$img_cont)){
                                        $pro_images[] = $base_url.'/'.trim($img_cont[1]);
                                    }

                                    eh('图片列表：');
                                    print_r($pro_images);

                                    //描述
                                    $pro_desc = '';
                                    $desc_images = array();
                                    if(preg_match('/<div class="cos-none" style="display: block;">([\s\S]*)<\/li>\s*<\/ul>\s*<\/div>\s*<\/div>\s*<script/Ui',$p_cont,$p_desc)){
                                        $pro_desc = $p_desc[1];
                                        if(preg_match_all('/<img\s*src="(.*)"/Ui',$pro_desc,$d_img)){
                                            if(sizeof($d_img[1])>0){
                                                foreach($d_img[1] as $d_i){
                                                    $desc_images[]= $base_url.'/'.trim($d_i);
                                                }
                                            }
                                        }
                                    }

                                    eh('描述中的图片:');
                                    print_r($desc_images);


                                    if(sizeof($desc_images)>0){
                                        foreach($desc_images as $d_im){
//                                    $img_name = getImageName($d_im);
//                                    $img_path = getImageCate($d_im);
//                                    //eh(getImageName(trim($d_im)));
//                                    eh($img_name);
//                                    eh($img_path);

                                            //下载图片
                                            saveImages($d_im,getImageName(trim($d_im)),$save_image_path.getImageCate($d_im));
                                            //替换描述中的图片链接
                                            $pro_desc=str_replace('bmz_cache/',$save_image_path,$pro_desc);
                                        }
                                    }



                                    //保存数据
                                    $product_data = array(
                                        'name' => $product_title,
                                        'model' => $pro_model,
                                        'weight' => isset($pro_height)?$pro_height:0.0,
                                        'master_categories_id' => $cate_id,
                                        'desc' => $pro_desc,
                                        'base_price' => trim($pro_price),
                                        'special' => trim($special_price),
                                        'image' => $pro_images
                                    );
                                    $pid = create_pf_product($product_data,1.00);
                                    if($pid){
                                        add_caiji_record($cate_id,trim($pro_list_url),trim($link_url),$pid);
                                        sleep(1);
                                        echo ': <font color="green">Y: </font>'.$link_url.'<br>';

                                    }else{
                                        echo ': <font color="red">N: </font>'.$link_url.'<br>';
                                    }
                            }
                        }
                        //exit;
                    }
                }

            }
        }


        //exit;

    }






    //echo htmlspecialchars($p_all_cont);
}





exit;
//分页
for ($i=1;$i<=4;$i++){
    $cat_url = $all_categories_url.'&page='.$i;
    eh("第{$i}页");
    eh('cate_url:'.$cat_url);
    //Start 获取所有大分类
    $all_categories_cont=curlGet($cat_url);

    //echo htmlspecialchars($all_categories_cont);
    if($all_categories_cont){
        //
        if(preg_match('/<ul class="product_list grid row">([\s\S]*)<\/ul>/Ui',$all_categories_cont,$category_text)){
            //商品列表
            if(preg_match_all('/<h6><a href="(.*)".*>.*<\/a><\/h6>/Ui',$category_text[1],$pro_link)){
                if(count($pro_link[1]) > 0 ){
                    foreach ($pro_link[1] as $p_l){
                        $p_url=trim($p_l);
                        $check=has_caiji_record($p_url);
                        if(!$check){
                            //进入每一个产品
                            $p_cont=curlGet($p_url);
                            sleep(1);
                            //echo $p_cont;
                            if($p_cont){

                                //产品标题
                                $pro_title='';
                                if(preg_match('/<div class="pb-center-column col-xs-12 col-md-6 col-lg-6">\s*<h2>(.*)<\/h2>/Ui', $p_cont,$p_title)){
                                    $pro_title=trim($p_title[1]);
                                }
                                eh('产品标题：'.$pro_title);
                                eh('产品链接：'.$p_url);

                                //产品分类
                                $category_id =0;
                                $c_name = '';
                                if(preg_match('/<div class="container"><span>([\s\S]*)<\/div>/Ui', $p_cont,$cat_con)){
                                    //eh(htmlspecialchars($cat_con[1]));
                                    //exit;
                                    if($cat_con[1]){
                                        if(preg_match_all('/<a href=".*"><span>(.*)<\/span><\/a>/i', $cat_con[1], $cate_links)){
                                            //array_shift($cate_links[1]);
                                            //array_pop($cate_links[1]);
                                            //print_r($cate_links[1]);exit;
                                            if(sizeof($cate_links[1])>0){
                                                foreach ($cate_links[1] as $c_name) {
                                                    $category_id = create_category(trim($c_name),$category_id,$language_id);
                                                }
                                            }
                                        }
                                    }
                                }
                                //exit;
                                //End 产品分类
                                eh('分类ID:'.$category_id);
                                eh('分类名称：'.$c_name);

                                //产品Model
                                $pro_model = '';
                                if(preg_match('/<b>Model:<\/b>\s*(.*)\s*<\/div>/Ui',$p_cont,$p_model)){
                                    $pro_model = trim($p_model[1]);
                                }
                                eh("产品model:{$pro_model}");
                                //exit;


                                //产品重量

                                //产品原价
                                $old_price = 0.00;
                                if(preg_match('/<span class="old-price">(.*)<\/span>/Ui',$p_cont,$old_p)){
                                    echo $old_p[1];
                                    $old_price = trim(trim($old_p[1]),'€');
                                }
                                eh("原价：{$old_price}");

                                //产品价格
                                $pro_price=0;
                                if(preg_match('/<span id="our_price_display" class="our_price_display">(.*)<\/span>/Ui',$p_cont,$p_price)){
                                    $pro_price=trim(trim($p_price[1]),'$');
                                }
                                eh('产品价格：'.$pro_price);
                                //exit;

                                //产品图
                                $pro_images=array();
                                //主图
                                if(preg_match('/<span id="imgLight1">\s*<script language="javascript" type="text\/javascript"><\!--\s*document.write\(\'<a href="(.*)" rel=/Ui',$p_cont,$main_i)){
                                    //echo $main_i[1];
                                    $pro_images[] = $base_url.'/'.trim($main_i[1]);
                                }

                                if(preg_match('/<ul class="slides ">([\s\S]*)<\/ul>/Ui', $p_cont,$img_cont)){
                                    //echo $img_cont[1];
                                    if($img_cont[1]){
                                        if(preg_match_all('/document.write\(\'<a href="(.*)" rel=/Ui', $img_cont[1], $img_list)){
                                            //print_r($img_list);exit;
                                            if(sizeof($img_list[1])>0){
                                                foreach($img_list[1] as $img){
                                                    $pro_images[] = $base_url.'/'.trim($img);
                                                }
                                            }
                                        }
                                    }
                                }
                                eh('图片列表：');
                                print_r($pro_images);

                                //产品描述
                                $pro_desc='';
                                $desc_images=array();

                                //if(preg_match('/<section id="descriptionshortTab"([\s\S]*)<\/section>/Ui',$p_cont,$p_desc1)){
                                if(preg_match('/<a href="#descriptionshortTab" data-toggle="tab">Products Detail<\/a><\/li><\/ul><\/div>([\s\S]*)<\/section>\s*<\/div>\s*<\/div>\s*<section class="page-product-box flexslider_carousel_block blockproductscategory">\s*<h3 class="productscategory_h3 page-product-heading">You Might also like<\/h3>/Ui',$p_cont,$p_desc1)){
                                    //echo htmlspecialchars($p_desc1[1]);
                                    if($p_desc1[1]){
                                        $pro_desc = trim($p_desc1[1]);
                                        //echo htmlspecialchars($p_desc1[1]);
                                        if(preg_match_all('/<img[\s\S]*src="(.*)"/Ui',$p_desc1[1],$d_img)){
                                            //var_dump($d_img);
                                            if(sizeof($d_img[1]) > 0 ){
                                                foreach($d_img[1] as $d_i){
                                                    $desc_images[]= trim($d_i);
                                                }
                                            }
                                        }
                                    }

                                    if(sizeof($desc_images) > 0){

                                        foreach($desc_images as $d_im){
                                            $d_im = $base_url.'/'.$d_im;
                                            eh(getImageName(trim($d_im)));

                                            //下载图片
                                            saveImages($d_im,getImageName(trim($d_im)),$save_image_path.getImageCate($d_im));
                                            //替换描述中的图片链接
                                            $pro_desc=str_replace($d_im,$save_image_path.$d_im,$pro_desc);
                                        }
                                    }

                                }
                                eh('描述 图片');
                                print_r($desc_images);


                                //保存数据
                                $product_data = array(
                                    'name' => $pro_title,
                                    'model' => $pro_model,
                                    'weight' => isset($pro_height)?$pro_height:0.0,
                                    'master_categories_id' => $category_id,
                                    'desc' => $pro_desc,
                                    'base_price' => trim($old_price),
                                    'special' => trim($pro_price),
                                    'image' => $pro_images
                                );
                                $pid = create_pf_product($product_data,1.00);
                                if($pid){
                                    //products_to_categories($pid,$category_id,$db);

                                    //产品属性列表
                                    /*
                                    if(preg_match('/<select name="option\[.*\]">([\s\S]*)<\/select>/Ui',$p_cont,$attr_cont)){
                                        if($attr_cont[1]){
                                            if(preg_match_all('/<option.*>([\s\S]*)<\/option>/Ui',$attr_cont[1],$attr)){
                                                $option_id=options('Size',$db,$language_id);
                                                if(count($attr[1])>0){
                                                    foreach ($attr[1] as $nn=>$opt_v) {
                                                        eh('属性值：'.$opt_v);
                                                        $option_value_id=options_values(trim($opt_v),$db,$language_id);
                                                        values_to_options($option_id,$option_value_id,$db,$language_id);
                                                        if($nn==0){
                                                            $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id,0,1);
                                                        }else{
                                                            $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id);
                                                        }
                                                    }

                                                }

                                            }
                                        }
                                    }
                                    */

                                    //产品属性选项
                                    /*
                                    if(preg_match_all('/<td height="30">(.*):<select.*>([\s\S]*)<\/select><\/td>/Ui', $p_cont,$opt_cont)){
                                        //print_r($opt_cont);
                                        if(sizeof($opt_cont[1])>0){
                                            foreach($opt_cont[1] as $num=>$opt_name){
                                                eh('选项名：'.$opt_name);
                                                $option_id=options(trim($opt_name),$db,$language_id);
                                                //属性
                                                if(preg_match_all('/<option.*>(.*)<\/option>/Ui', $opt_cont[2][$num], $opt_value)){
                                                    if(sizeof($opt_value[1]>0)){
                                                        array_unshift($opt_value[1],'Please Select');
                                                        foreach ($opt_value[1] as $nn=>$opt_v) {
                                                            eh('属性值：'.$opt_v);
                                                            $option_value_id=options_values(trim($opt_v),$db,$language_id);
                                                            values_to_options($option_id,$option_value_id,$db,$language_id);
                                                            if($nn==0){
                                                                $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id,0,1);
                                                            }else{
                                                                $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    */
                                    //End 产品属性

                                    add_caiji_record($category_id,trim($cat_url),trim($p_url),$pid);

                                    sleep(1);
                                    echo ': <font color="green">Y: </font>'.$p_url.'<br>';

                                }else{
                                    echo ': <font color="red">N: </font>'.$p_url.'<br>';
                                }

                                //exit;

                            }
                        }else{
                            eh($p_url.'该产品已经采集过了！');
                        }


                    }
                }
                //print_r($pro_link[1]);
            }
            //echo $category_text[1];exit;

        }
    }


}



//sleep(0.5);
exit;
if($all_categories_cont){
    if(preg_match('/<ul class="level0">([\s\S]*)<\/ul>/Ui',$all_categories_cont,$category_text)){
        //echo $category_text[1];

        if(preg_match_all('/<a.*href="(.*)".*>.*<\/a>/Ui',$category_text[1],$cat_links)){
           // print_r($cat_links);
            if(count($cat_links[1])>0){
                foreach($cat_links[1] as $c_url){
                    //echo $c_url.'<br>';
                    $c_cont = curlGet($c_url);
                    sleep(0.5);
                    if($c_cont){
                        eh($c_url);
                        $page_amount =1;
                        //页数
                        if(preg_match('/<div class="results">Showing \d* to \d* of \d* \((\d*) Pages\)<\/div>/Ui',$c_cont,$p_amount)){
                            //print_r($p_amount);
                            $page_amount = $p_amount[1];
                        }
                        eh('总页数：'.$page_amount);
                        //进入每一页
                        for($page = 1;$page<=$page_amount;$page++){
                            $p_c_url=$c_url.'?page='.$page;
                            eh($p_c_url);
                            $p_c_cont=curlGet($p_c_url);
                            sleep(0.5);
                            if($p_c_cont){
                                eh('第'.$page.'页');
                                eh($p_c_url);

                                //获取该分类下所有产品列表
                                $pro_list_pattern='/<div class="name"><a href="(.*)".*>.*<\/a><\/div>/Ui';
                                if(preg_match_all($pro_list_pattern, $p_c_cont, $pro_lists)){
                                    //print_r($pro_lists[1]);
                                    //exit;
                                    if(sizeof($pro_lists[1])>0){
                                        foreach ($pro_lists[1] as $p_l) {
                                            $p_url=trim($p_l);
                                            $check=has_caiji_record($p_url,$db);
                                            if(!$check){
                                                //进入每一个产品
                                                $p_cont=curlGet($p_url);
                                                sleep(1);
                                                if($p_cont){

                                                    //产品标题
                                                    $pro_title='';
                                                    if(preg_match('/<div class="right">\s*<h2>(.*)<\/h2>/Ui', $p_cont,$p_title)){
                                                        $pro_title=trim($p_title[1]);
                                                    }
                                                    eh('产品标题：'.$pro_title);
                                                    eh('产品链接：'.$p_url);

                                                    //产品分类
                                                    $category_id=0;
                                                    if(preg_match('/<div class="breadcrumb">([\s\S]*)<\/div>/Ui', $p_cont,$cat_con)){
                                                        //eh(htmlspecialchars($cat_con[1]));
                                                        //exit;
                                                        if($cat_con[1]){
                                                            if(preg_match_all('/<a href=".*">(.*)<\/a>/i', $cat_con[1], $cate_links)){
                                                                array_shift($cate_links[1]);
                                                                array_pop($cate_links[1]);
                                                                //print_r($cate_links[1]);exit;
                                                                if(sizeof($cate_links[1])>0){
                                                                    foreach ($cate_links[1] as $c_name) {
                                                                        $category_id=create_category(trim($c_name),$db,$category_id,$language_id);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    //End 产品分类
                                                    eh('分类ID:'.$category_id);

                                                    //产品Model
                                                    $pro_model='';


                                                    //产品重量


                                                    //产品价格
                                                    $pro_price=0;
                                                    if(preg_match('/<span class="price-new">(.*)<\/span>/Ui',$p_cont,$p_price)){
                                                        $pro_price=trim(trim($p_price[1]),'$');
                                                        //print_r($p_price);
                                                    }
                                                    eh('产品价格：'.$pro_price);

                                                    //产品图
                                                    $pro_images=array();
                                                    if(preg_match('/<div class="image-additional">([\s\S]*)<\/div>/Ui', $p_cont,$img_cont)){
                                                        //echo $img_cont[1];
                                                        if($img_cont[1]){
                                                            if(preg_match_all('/<a href="(.*)".*<\/a>/Ui', $img_cont[1], $img_list)){
                                                                //print_r($img_list);exit;
                                                                if(sizeof($img_list[1])>0){
                                                                    foreach($img_list[1] as $img){
                                                                        $pro_images[]=trim($img);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    eh('图片列表：');
                                                    print_r($pro_images);

                                                    echo '</pre>';
                                                    //产品描述
                                                    $pro_desc='';
                                                    $desc_images=array();
                                                    if(preg_match('/<b style="line-height:40px">DETAILS<\/b>([\s\S]*)<div class="price">/Ui',$p_cont,$p_desc)){
                                                        $pro_desc=$p_desc[1];
                                                        //echo $p_desc[1];exit;
                                                        if(preg_match_all('/<IMG.*src="(.*)">/Ui',$p_desc[1],$d_img)){
                                                            if(sizeof($d_img[1])>0){
                                                                foreach($d_img[1] as $d_i){
                                                                    $desc_images[]=trim($d_i);
                                                                }
                                                            }
                                                        }
                                                        eh('产品描述');
                                                        //eh($pro_desc);
                                                        eh('描述中的图片:');
                                                        print_r($desc_images);
                                                        if(sizeof($desc_images)>0){
                                                            foreach($desc_images as $d_im){
                                                                eh(getImageName(trim($d_im)));

                                                                //下载图片
                                                                saveImages($d_im,getImageName(trim($d_im)),$save_image_path.getImageCate($d_im));
                                                                //替换描述中的图片链接
                                                                $pro_desc=str_replace('src="http://www.jackcare.com/pic/','src="images/desc/',$pro_desc);
                                                            }
                                                        }
                                                    }

                                                    //eh($pro_desc);
                                                    //exit;




                                                    //exit;



                                                    //保存数据
                                                    $product_data = array(
                                                        'name' => $pro_title,
                                                        'model' => $pro_model,
                                                        'weight' => isset($pro_height)?$pro_height:0.0,
                                                        'master_categories_id' => $category_id,
                                                        'desc' => $pro_desc,
                                                        'base_price' => trim($pro_price),
                                                        //'special' => trim($special_price),
                                                        'image' => $pro_images
                                                    );
                                                    $pid = create_pf_product($product_data,1.00,$db);
                                                    if($pid){
                                                        products_to_categories($pid,$category_id,$db);

                                                        //产品属性列表
                                                        if(preg_match('/<select name="option\[.*\]">([\s\S]*)<\/select>/Ui',$p_cont,$attr_cont)){
                                                            if($attr_cont[1]){
                                                                if(preg_match_all('/<option.*>([\s\S]*)<\/option>/Ui',$attr_cont[1],$attr)){
                                                                    //print_r($attr);
                                                                    $option_id=options('Size',$db,$language_id);
                                                                    if(count($attr[1])>0){
                                                                        foreach ($attr[1] as $nn=>$opt_v) {
                                                                            eh('属性值：'.$opt_v);
                                                                            $option_value_id=options_values(trim($opt_v),$db,$language_id);
                                                                            values_to_options($option_id,$option_value_id,$db,$language_id);
                                                                            if($nn==0){
                                                                                $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id,0,1);
                                                                            }else{
                                                                                $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id);
                                                                            }
                                                                        }

                                                                    }

                                                                }
                                                            }
                                                        }

                                                        //产品属性选项
                                                        /*
                                                        if(preg_match_all('/<td height="30">(.*):<select.*>([\s\S]*)<\/select><\/td>/Ui', $p_cont,$opt_cont)){
                                                            //print_r($opt_cont);
                                                            if(sizeof($opt_cont[1])>0){
                                                                foreach($opt_cont[1] as $num=>$opt_name){
                                                                    eh('选项名：'.$opt_name);
                                                                    $option_id=options(trim($opt_name),$db,$language_id);
                                                                    //属性
                                                                    if(preg_match_all('/<option.*>(.*)<\/option>/Ui', $opt_cont[2][$num], $opt_value)){
                                                                        if(sizeof($opt_value[1]>0)){
                                                                            array_unshift($opt_value[1],'Please Select');
                                                                            foreach ($opt_value[1] as $nn=>$opt_v) {
                                                                                eh('属性值：'.$opt_v);
                                                                                $option_value_id=options_values(trim($opt_v),$db,$language_id);
                                                                                values_to_options($option_id,$option_value_id,$db,$language_id);
                                                                                if($nn==0){
                                                                                    $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id,0,1);
                                                                                }else{
                                                                                    $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id);
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        */
                                                        //End 产品属性

                                                        add_caiji_record($category_id,trim($c_url),$p_url,$pid,$db);

                                                        sleep(1);
                                                        echo ': <font color="green">Y: </font>'.$p_url.'<br>';

                                                    }else{
                                                        echo ': <font color="red">N: </font>'.$p_url.'<br>';
                                                    }

                                                    //exit;

                                                }
                                            }else{
                                                eh($p_url.'该产品已经采集过了！');
                                            }

                                            //exit;
                                        }
                                    }
                                }



                            }

                        }


                    }

                }
            }

        }
        //var_dump($category_text);
    }

exit;
    if(preg_match_all('/<a style=" font-weight: bold;".*href="(.*)">.*<\/a>/Ui', $all_categories_cont,$cat_links)){
        //print_r($cat_links[1]);
        //进入每一个分类
        if(sizeof($cat_links[1])>0){
            foreach ($cat_links[1] as $c_link) {
                $c_url=$base_url.'/'.trim($c_link);
                $c_cont=curlGet($c_url);
                sleep(0.5);
                if($c_cont){
                    eh($c_url);
                    //获取总页数
                    $page_amount=0;
                    if(preg_match('/Page:1\/Total:(\d*)&nbsp;/Ui',$c_cont,$p_amount)){
                        //print_r($p_amount);
                        $page_amount=$p_amount[1];
                        eh('总页数：'.$page_amount);
                        //进入每一页
                        for($page=1;$page<=$page_amount;$page++){
                            $p_c_url=$c_url.'&order=&lookt=shu&page='.$page;
                            $p_c_cont=curlGet($p_c_url);
                            sleep(0.5);
                            if($p_c_cont){
                                eh('第'.$page.'页');
                                eh($p_c_url);
                                //获取该大分类下所有产品列表
                                $pro_list_pattern='/<div class="ititletd"><a.*href="(.*)".*>.*<\/a><\/div>/Ui';
                                if(preg_match_all($pro_list_pattern, $p_c_cont, $pro_lists)){
                                    //print_r($pro_lists[1]);
                                    if(sizeof($pro_lists[1])>0){
                                        foreach ($pro_lists[1] as $p_l) {
                                            $p_url=$base_url.'/'.trim($p_l);
                                            $check=has_caiji_record($p_url,$db);
                                            if(!$check){
                                                //进入每一个产品
                                                $p_cont=curlGet($p_url);
                                                sleep(1);
                                                if($p_cont){
                                                    //产品标题
                                                    $pro_title='';
                                                    if(preg_match('/<td class="vtitle">(.*)<\/td>/Ui', $p_cont,$p_title)){
                                                        $pro_title=trim($p_title[1]);
                                                    }
                                                    eh('产品标题：'.$pro_title);
                                                    eh('产品链接：'.$p_url);

                                                    //产品分类
                                                    $category_id=0;
                                                    if(preg_match('/<td class="sort">([\s\S]*)<\/td>/Ui', $p_cont,$cat_con)){
                                                        //eh(htmlspecialchars($cat_con[1]));
                                                        if($cat_con[1]){
                                                            if(preg_match_all('/<a href=".*">(.*)<\/a>/Ui', $cat_con[1], $cate_links)){
                                                                array_shift($cate_links[1]);
                                                                //print_r($cate_links[1]);
                                                                if(sizeof($cate_links[1])>0){
                                                                    foreach ($cate_links[1] as $c_name) {
                                                                        $category_id=create_category(trim($c_name),$db,$category_id,$language_id);
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    //End 产品分类
                                                    eh('分类ID:'.$category_id);

                                                    //产品Model
                                                    $pro_model='';
                                                    if(preg_match('/<td height="23">Item#:(.*)<\/td>/Ui', $p_cont,$p_model)){
                                                        $pro_model=$p_model[1];
                                                    }
                                                    eh('产品Model: '.$pro_model);

                                                    //产品重量
                                                    if(preg_match('/<td height="23">Weight:(.*)kg.<\/td>/Ui', $p_cont,$p_height)){
                                                        $pro_height=trim($p_height[1]);
                                                        eh('产品重量：'.$pro_height);
                                                    }

                                                    //产品价格
                                                    $pro_price=0;
                                                    if(preg_match('/<span class="price"><script>document.write\(cheng(?:\()(.*)(?:\))\)<\/script><\/span>/Ui',$p_cont,$p_price)){
                                                        $pro_price=trim($p_price[1]);
                                                        print_r($p_price);
                                                    }
                                                    eh('产品价格：'.$pro_price);

                                                    //产品图
                                                    $pro_images=array();
                                                    if(preg_match('/<p style="line-height: 150%; margin: 5px 0px;">([\s\S]*)<\/p>/Ui', $p_cont,$img_cont)){
                                                        if($img_cont[1]){
                                                            if(preg_match_all('/<img.*src="(.*)".*>/Ui', $img_cont[1], $img_list)){
                                                                //print_r($img_list);
                                                                if(sizeof($img_list[1])>0){
                                                                    foreach($img_list[1] as $img){
                                                                        $pro_images[]=$base_url.str_replace('..','',trim($img));
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    eh('图片列表：');
                                                    print_r($pro_images);

                                                    //产品描述
                                                    $pro_desc='';
                                                    $desc_images=array();
                                                    if(preg_match('/<td valign="top" style="line-height: 150%; margin: 3">([\s\S]*)<\/td>/Ui',$p_cont,$p_desc)){
                                                        $pro_desc=$p_desc[1];
                                                        //echo $p_desc[1];
                                                        if(preg_match_all('/<IMG.*src="(.*)">/Ui',$p_desc[1],$d_img)){
                                                            if(sizeof($d_img[1])>0){
                                                                foreach($d_img[1] as $d_i){
                                                                    $desc_images[]=trim($d_i);
                                                                }
                                                            }
                                                        }
                                                        eh('产品描述');
                                                        //eh($pro_desc);
                                                        eh('描述中的图片:');
                                                        print_r($desc_images);
                                                        if(sizeof($desc_images)>0){
                                                            foreach($desc_images as $d_im){
                                                                eh(getImageName(trim($d_im)));

                                                                //下载图片
                                                                saveImages($d_im,getImageName(trim($d_im)),$save_image_path.getImageCate($d_im));
                                                                //替换描述中的图片链接
                                                                $pro_desc=str_replace('src="http://www.jackcare.com/pic/','src="images/desc/',$pro_desc);
                                                            }
                                                        }
                                                    }

                                                    eh($pro_desc);





                                                    //保存数据
                                                    $product_data = array(
                                                        'name' => $pro_title,
                                                        'model' => $pro_model,
                                                        'weight' => isset($pro_height)?$pro_height:0.0,
                                                        'master_categories_id' => $category_id,
                                                        'desc' => $pro_desc,
                                                        'base_price' => trim($pro_price),
                                                        //'special' => trim($special_price),
                                                        'image' => $pro_images
                                                    );
                                                    $pid = create_pf_product($product_data,1.00,$db);
                                                    if($pid){
                                                        products_to_categories($pid,$category_id,$db);

                                                        //产品属性选项
                                                        if(preg_match_all('/<td height="30">(.*):<select.*>([\s\S]*)<\/select><\/td>/Ui', $p_cont,$opt_cont)){
                                                            //print_r($opt_cont);
                                                            if(sizeof($opt_cont[1])>0){
                                                                foreach($opt_cont[1] as $num=>$opt_name){
                                                                    eh('选项名：'.$opt_name);
                                                                    $option_id=options(trim($opt_name),$db,$language_id);
                                                                    //属性
                                                                    if(preg_match_all('/<option.*>(.*)<\/option>/Ui', $opt_cont[2][$num], $opt_value)){
                                                                        if(sizeof($opt_value[1]>0)){
                                                                            array_unshift($opt_value[1],'Please Select');
                                                                            foreach ($opt_value[1] as $nn=>$opt_v) {
                                                                                eh('属性值：'.$opt_v);
                                                                                $option_value_id=options_values(trim($opt_v),$db,$language_id);
                                                                                values_to_options($option_id,$option_value_id,$db,$language_id);
                                                                                if($nn==0){
                                                                                    $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id,0,1);
                                                                                }else{
                                                                                    $attribute_id=attributes($pid,$option_id,$option_value_id,$db,$language_id);
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        //End 产品属性

                                                        add_caiji_record($category_id,trim($c_url),$p_url,$pid,$db);

                                                        sleep(1);
                                                        echo ': <font color="green">Y: </font>'.$p_url.'<br>';

                                                    }else{
                                                        echo ': <font color="red">N: </font>'.$p_url.'<br>';
                                                    }



                                                    //exit;

                                                }
                                            }else{
                                                eh($p_url.'该产品已经采集过了！');
                                            }


                                            //exit;
                                        }
                                    }
                                }
                            }

                            //exit;
                            


                        }
                    }
                    //exit;

                }
                
            }
        }
    }
}













