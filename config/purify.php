<?php

use Stevebauman\Purify\Definitions\Html5Definition;

return [

    'default' => 'default',

    'configs' => [

        'default' => [
            'Core.Encoding' => 'utf-8',
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'h1,h2,h3,h4,h5,h6,b,u,strong,i,em,s,del,a[href|title],ul,ol,li,p[style],br,span,img[width|height|alt|src],blockquote',
            'HTML.ForbiddenElements' => '',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
        ],

        /**
         * Rich article HTML from CKEditor (blog-style): tables, media, alignment, etc.
         */
        'article' => [
            'Core.Encoding' => 'utf-8',
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => implode(',', [
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'p[style|class]', 'div[style|class]', 'br', 'hr',
                'strong', 'b', 'em', 'i', 'u', 's', 'strike', 'del', 'sub', 'sup',
                'blockquote[class|style]', 'pre', 'code',
                'a[href|title|target|rel|class]',
                'ul[class|style]', 'ol[class|style]', 'li[class|style]',
                'img[src|alt|title|width|height|class|style]',
                'table[class|style]', 'caption', 'thead', 'tbody', 'tfoot', 'tr', 'th[colspan|rowspan|style|class]', 'td[colspan|rowspan|style|class]',
                'figure[class|style]', 'figcaption',
                'span[style|class]',
            ]),
            'HTML.ForbiddenElements' => '',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding,padding-left,padding-right,padding-top,padding-bottom,margin,margin-left,margin-right,margin-top,margin-bottom,color,background-color,background,text-align,border,border-collapse,width,max-width,height,float',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
        ],
    ],

    'definitions' => Html5Definition::class,
    'css-definitions' => null,

    'serializer' => [
        'driver' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),
        'cache' => \Stevebauman\Purify\Cache\CacheDefinitionCache::class,
    ],
];
