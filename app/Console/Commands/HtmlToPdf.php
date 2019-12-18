<?php

namespace App\Console\Commands;

use App\Http\Controllers\ProducePdf\ProducePdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class HtmlToPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:doc_pdf {--TALSSO=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成通用组件平台PDF版文档 php artisan create:doc_pdf --TALSSO="扫码登录后cookie中TALSSO';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo 'start wait a minute';

        $TALSSO = $this->options('TALSSO');

        $TALSSO = "TALSSO=".$TALSSO['TALSSO'];

        $config = Config('htmltopdf');

        //文件置空
        if(File::exists(($config['HTML_PATH']))){

            File::put(($config['HTML_PATH']),' ');
        }

        $pdf = new ProducePdf();

        $res = $pdf->createPdf($TALSSO);

        if($res['errcode'] == 0)
            return  'OK';
        return 'fault';
    }
}
