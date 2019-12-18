<?php
return [

    //PDF存储路径
    'SAVE_PDF_PATH' => "/Users/tal/Downloads/",

    //HTML（转PDF所需）文件路径
    'HTML_PATH' => resource_path('views/html_to_pdf/html_to_pdf.html'),

    //需要访问的域名
    'DOMAIN_NAME' => '',

    //生成文件的文件格式
    'PDF_FILE_TYPE' => '.pdf',

    //生成PDF的文件名
    'PDF_FILE_NAME' => '',

    //HTML（转PDF所需）文件路由
    'CREATE_PDF_ROUTE' => '/create/pdf/loading',

    //访问网页路由目录
    'DOC_DIRECTORY_PATH' => resource_path('docs/zws/index.md'),

];