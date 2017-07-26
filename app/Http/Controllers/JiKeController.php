<?php
/**
 * Created by PhpStorm.
 * User: ZhangWei
 * Date: 2017/7/26
 * Time: 9:36
 */

namespace App\Http\Controllers;

use App\Extend\AliMNS;
use League\HTMLToMarkdown\HtmlConverter;
use QL\QueryList;

class JiKeController extends Controller
{

    public function content()
    {

        $msn = new AliMNS();

        $s = $msn->create("cloud-doc","cloud-doc","https://cloud-doc.leyix.com/jike");

        dd($s);

        error_reporting(0);
        $url = "http://wiki.jikexueyuan.com/project/html5/";
        $url = "http://wiki.jikexueyuan.com/project/chrome-devtools/console-api-reference.html";

        $client = new \GuzzleHttp\Client();

        $html = $client->get($url)->getBody();
        $rules = array(
            'content' => array('.markdown-body', 'html'),
        );
        $data = QueryList::Query($html, $rules, '.detail-main')->data;

        $content = $data[0]['content'];

        $content = $this->formaturl($content, $url);
        $converter = new HtmlConverter();
        $markdown = $converter->convert($content);

        preg_match_all("/<table.*?>(.*?)<\/table>/is", $markdown, $arg);


        foreach ($arg as $v) {
            if ($v[0] != null) {
                $markdown = preg_replace("/<table.*?>(.*?)<\/table>/is", $this->_setTable($v[0]), $markdown);
            }
        }

        return $markdown;

    }

    private function _setTable($table)
    {

        $th = QueryList::Query($table, array(
            'th' => array('thead>tr>th', 'text'),
        ))->data;
        if (empty($th)) {
            $th = QueryList::Query($table, array(
                'th' => array('tr:eq(0)>td', 'text'),
            ))->data;
        }




        $tr = QueryList::Query($table, array(
            'tr' => array('tbody>tr', 'html'),
        ))->getData(function ($item) {
            $item['tr'] = QueryList::Query($item['tr'], array(
                'td' => array('td', 'text')
            ))->data;

            return $item;
        });
        if (empty($tr)) {
            $tr = QueryList::Query($table, array(
                'tr' => array('tr:gt(0)', 'html'),
            ))->getData(function ($item) {
                $item['tr'] = QueryList::Query($item['tr'], array(
                    'td' => array('td', 'text')
                ))->data;

                return $item;
            });
        }
        $t = "|";
        $b = "|";
        foreach ($th as $k => $v) {
            $t .= $v['th'] . "|";
            $b .= " -------- " . "|";
        }
        $a = "";
        foreach ($tr as $k => $v) {
            $a .= "|";
            foreach ($v['tr'] as $vv) {
                $a .= $vv['td'] . "|";
            }
            $a .= "\n";
        }

        $table_markdown = $t . "\n" . $b . "\n" . $a;
        return $table_markdown;
    }


    private function formaturl($l1, $l2)
    {
        if (preg_match_all("/(<img[^>]+src=\"([^\"]+)\"[^>]*>)|(<a[^>]+href=\"([^\"]+)\"[^>]*>)|(<img[^>]+src='([^']+)'[^>]*>)|(<a[^>]+href='([^']+)'[^>]*>)/i", $l1, $regs)) {
            foreach ($regs [0] as $num => $url) {
                $l1 = str_replace($url, $this->lIIIIl($url, $l2), $l1);
            }
        }
        return $l1;
    }

    private function lIIIIl($l1, $l2)
    {
        if (preg_match("/(.*)(href|src)\=(.+?)( |\/\>|\>).*/i", $l1, $regs)) {
            $I2 = $regs [3];
        }
        if (strlen($I2) > 0) {
            $I1 = str_replace(chr(34), "", $I2);
            $I1 = str_replace(chr(39), "", $I1);
        } else {
            return $l1;
        }
        $url_parsed = parse_url($l2);

        $scheme = $url_parsed ["scheme"];
        if ($scheme != "") {
            $scheme = $scheme . "://";
        }
        $host = $url_parsed ["host"];
        $l3 = $scheme . $host;
        if (strlen($l3) == 0) {
            return $l1;
        }

        //$path = dirname($url_parsed["path"]);

        $path = pathinfo($url_parsed["path"]);

        $path = $path['dirname'] . '/' . $path['basename'];

        if ($path [0] == "\\") {
            $path = "";
        }
        $pos = strpos($I1, "#");
        if ($pos > 0)
            $I1 = substr($I1, 0, $pos);

        //判断类型
        if (preg_match("/^(http|https|ftp):(\/\/|\\\\)(([\w\/\\\+\-~`@:%])+\.)+([\w\/\\\.\=\?\+\-~`@\':!%#]|(&amp;)|&)+/i", $I1)) {
            return $l1;
        } //http开头的url类型要跳过
        elseif ($I1 [0] == "/") {
            $I1 = $l3 . $I1;
        } //绝对路径
        elseif (substr($I1, 0, 3) == "../") { //相对路径
            while (substr($I1, 0, 3) == "../") {
                $I1 = substr($I1, strlen($I1) - (strlen($I1) - 3), strlen($I1) - 3);
                if (strlen($path) > 0) {
                    $path = dirname($path);
                }
            }
            $I1 = $l3 . $path . "/" . $I1;
        } elseif (substr($I1, 0, 2) == "./") {


            $I1 = $l3 . $path . substr($I1, strlen($I1) - (strlen($I1) - 1), strlen($I1) - 1);


        } elseif (strtolower(substr($I1, 0, 7)) == "mailto:" || strtolower(substr($I1, 0, 11)) == "javascript:") {
            return $l1;
        } else {
            $I1 = $l3 . $path . "/" . $I1;
        }
        return str_replace($I2, "\"$I1\"", $l1);
    }

    public function index()
    {
        error_reporting(0);
        $url = "http://wiki.jikexueyuan.com/project/html5/";

        $client = new \GuzzleHttp\Client();

        $html = $client->get($url)->getBody();

        $rules = array(
            'title' => array('>.detail-navlist-title>a', 'text'),
            'href' => array('>.detail-navlist-title>a', 'href'),
            'list' => array('>.navul-one', 'html')
        );

        $data = QueryList::Query($html, $rules, ".detail-nav>.navlist-one")->getData(function ($item) {
            $item['list'] = QueryList::Query($item['list'], array(
                'title' => array('>.detail-navlist-title>a', 'text'),
                'href' => array('>.detail-navlist-title>a', 'href'),
                'list' => array('>.navul-two', 'html')
            ), '.detail-navlist')->getData(function ($item2) {
                $item2['list'] = QueryList::Query($item2['list'], array(
                    'title' => array('.detail-navlist-title>a', 'text'),
                    'href' => array('.detail-navlist-title>a', 'href'),
                ))->data;
                return $item2;
            });
            return $item;
        });

        return $data;
    }

}