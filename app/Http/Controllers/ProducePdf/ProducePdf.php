<?php

namespace App\Http\Controllers\ProducePdf;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Parsedown;

class ProducePdf extends Controller
{
    protected $cookies;

    public function __construct()
    {
        $config = Config('htmltopdf');

        $this->markdownParser = new Parsedown();

        $this->SAVE_PDF_PATH = $config['SAVE_PDF_PATH'];

        $this->HTML_PATH = $config['HTML_PATH'];

        $this->DOMAIN_NAME = $config['DOMAIN_NAME'];

        $this->PDF_FILE_TYPE = $config['PDF_FILE_TYPE'];

        $this->PDF_FILE_NAME = $config['PDF_FILE_NAME'];

        $this->CREATE_PDF_ROUTE = $config['CREATE_PDF_ROUTE'];

        $this->DOC_DIRECTORY_PATH = $config['DOC_DIRECTORY_PATH'];
    }

    //获取文档文件目录,使用正则去除不要的与不识别的标签和样式代码
    public function getDirectory($directory)
    {
        $fileContent = file_get_contents($directory);

        $doc_name = $this->convertMarkdownToHtml($fileContent);

        $doc_name_array = explode("</ul>", $doc_name);

        foreach ($doc_name_array as $value) {

            $strPattern = '/<h2>(.*?)<\/h2>/si';//正则匹配<h2>标签

            preg_match_all($strPattern, $value, $Tags);

            $mapping_a = "/<a .*?>(.*?)<\/a>/";//正则匹配<a>标签

            preg_match_all($mapping_a, $value, $childTags);

            $childTags = $childTags[0];

            foreach ($childTags as $v) {

                preg_match($mapping_a, $v, $child_Tag_desc_single);//单<a>标签描述

                $mapping_href = "/href=\"([^\"]+)/";//正则匹配<a>标签中href值

                preg_match_all($mapping_href, $v, $href);//单<a>标签url

                $res[$Tags[1][0]][$href[1][0]] = $child_Tag_desc_single[1];
            }
        }
        return $res;
    }

    public function convertMarkdownToHtml($markdown)
    {
        $convertedHmtl = $this->markdownParser->setBreaksEnabled(true)->text($markdown);

        return $convertedHmtl;
    }

    //将html转成PDF
    public function createPdf($TALSSO)
    {
        Log::info("task start");

        Log::info('get_directory_start...');

        $this->cookies = $TALSSO;

        $directory_path = $this->DOC_DIRECTORY_PATH;

        try {

            $directory = $this->getDirectory($directory_path);

            Log::info('directory_finish AND create_html_start');

            foreach ($directory as $key => $value) {

                foreach ($value as $k => $v) {
                    dump($this->DOMAIN_NAME . $k);

                    $ch = curl_init($this->DOMAIN_NAME . $k);

                    Log::info('record_html_write_in_html_to_pdf.html -> ' . $this->DOMAIN_NAME . $k);

                    $this->saveDocumentHtml($ch);

                    Log::info($this->DOMAIN_NAME . $k.'=》 finish');

                    curl_close($ch);
                }
            }
            Log::info('create_html_finish');

            $h = '';
//        $h = " -O landscape "; //pdf横向页面不够,该设置是把页面纵向变横向
            Log::info('creat_pdf_start');

            $url = $this->DOMAIN_NAME . $this->CREATE_PDF_ROUTE; //源网页(需要生成pdf的网页地址,外网能访问的页面地址)

            $cmd = "wkhtmltopdf  " . $h . $url . " " . $this->SAVE_PDF_PATH . $this->PDF_FILE_NAME . $this->PDF_FILE_TYPE;

            Log::info('pdf-information-> ' . $cmd);

            shell_exec($cmd);

            Log::info('creat_pdf_finish');

        } catch (\Exception $e) {
            Log::info($e);

            return [
                'errcode' => 1,
                'errmsg' => 'fault'
            ];
        }
        Log::info("task ending");

        return [
            'errcode' => 0,
            'errmsg' => 'OK'
        ];
    }

    //生成html（转pdf所需）
    public function saveDocumentHtml($ch)
    {
        $ch = $this->sendSsoVerify($ch, $this->DOMAIN_NAME);

        $fileContent = curl_exec($ch);

        $mapping_html = "/<!doctype html>(.*?)<\/html>/is";

        preg_match_all($mapping_html, $fileContent, $filehtml);

        $fileContent = $filehtml[0][0];

        $mapping_nav = "/<nav .*?>(.*?)<\/nav>/is";

        $mapping_ul = "/<ul>(.*?)<\/ul>/is";

        $mapping_li = "/<li>(.*?)<\/li>/is";

        $useless_str_div = '<div id="app" v-cloak>';

        $use_str_div = '<div id="app">';

        $useless_srt_class = ':class="{\'expanded\': ! sidebar}"';

        $useless_str_one = '<div class="bg-gradient-primary text-white h-1"></div>';

        $useless_str_two = '<div class="sidebar" :class="[{\'is-hidden\': ! sidebar}]">';

        preg_match_all($mapping_nav, $fileContent, $href);

        $fileContent = str_replace($href[0][0], " ", $fileContent);

        $fileContent = str_replace($useless_str_one, " ", $fileContent);

        $fileContent = str_replace($useless_str_two, " ", $fileContent);

        preg_match_all($mapping_ul, $fileContent, $res);

        foreach ($res[0] as $v) {

            $fileContent = str_replace($v, " ", $fileContent);
        }
        preg_match_all($mapping_li, $fileContent, $res);

        foreach ($res[0] as $v) {

            $fileContent = str_replace($v, " ", $fileContent);
        }
        $fileContent = str_replace($useless_str_div, $use_str_div, $fileContent);

        $fileContent = str_replace($useless_srt_class, "", $fileContent);

        if (!File::exists($this->HTML_PATH)) {

            File::put($this->HTML_PATH, $fileContent);
        } else {

            File::append($this->HTML_PATH, $fileContent);
        }
    }

    //curl携带cookie
    public function sendSsoVerify($ch, $referer_)
    {
        $this_header = array("content-type: application/json; charset=UTF-8");

        curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
        curl_setopt($ch, CURLOPT_HEADER,1);

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');//获取的cookie 保存到指定的 文件路径

        //curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');//要发送的cookie文件，注意这里是文件，还一个是变量形式发送

        curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);

        $content=curl_exec($ch);

        if(curl_errno($ch)){
            Log::info('Curl error: '.curl_error($ch));
            echo 'Curl error: '.curl_error($ch);exit(); //这里是设置个错误信息的反馈
        }

        if($content==false){
            Log::info("get_content_null");
            echo "get_content_null";exit();
        }
        return $ch;
    }

    //HTML（转PDF所需）页面
    public function loading()
    {
        return view('html_to_pdf.html_to_pdf');
    }
}
