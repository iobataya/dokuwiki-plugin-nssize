<?php
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
class syntax_plugin_nssize extends DokuWiki_Syntax_Plugin {
  function getType(){    return 'substition';  }
  function getSort(){    return 150;  }
  function connectTo($mode) { $this->Lexer->addSpecialPattern('\{\{nssize>[^}]*\}\}',$mode,'plugin_nssize'); }

    // Handling lexer
    function handle($match, $state, $pos, Doku_Handler $handler){
        $match = substr($match,8,-2);
        if($match[0]=='>') $match = substr($match,1);
        return array($state,$match);
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        global $conf;
        if($mode!='xhtml') return false;
        $paths = array('datadir'   => 'pages',
                'olddir'    => 'attic',
                'mediadir'  => 'media',
                'mediaolddir' => 'media_attic',
                'metadir'   => 'meta',
                'mediametadir' => 'media_meta',
                'cachedir'  => 'cache',
                'indexdir'  => 'index',
                'lockdir'   => 'locks',
                'tmpdir'    => 'tmp');
        list($state, $match) = $data;
        $renderer->table_open(2);
        $this->_nssize_header($renderer,'Namespace','Size');
        $total = 0;
        foreach($paths as $c => $p) {
            if($conf['display_'.$c]===0) continue;
            $path = empty($conf[$c]) ? $conf['savedir'].'/'.$p.'/'.$match : $conf[$c].'/'.$match;
            $conf[$c] = init_path($path);
            $bytes = $this->_du($conf[$c]);
            $nssize = $this->_formatSize($bytes);
            $alert = ($bytes>$this->getConf('alert_size'));
            $name = $this->getConf('show_abs_path')===1?$path:$p.'/'.$match;
            $this->_row($renderer,$name,$nssize,$alert);
            $total = $total+$bytes;
        }
        if($this->getConf('display_sum')){
            $this->_nssize_header($renderer,'Sum',$this->_formatSize($total));
        }
        $renderer->table_close();
        return true;
    }

  /**
   * calculate disk usage
   * Author:  Gregor Mosheh
   * Website: http://php.net/manual/en/ref.filesystem.php
   * @param string $location
   */
  function _du($location) {
       if (!$location or !is_dir($location)) {
          return 0;
       }
       $total = 0;
       $all = opendir($location);
       while ($file = readdir($all)) {
          if (is_dir($location.'/'.$file) and $file <> ".." and $file <> ".") {
             $total += $this->_du($location.'/'.$file);
             unset($file);
          }
          elseif (!is_dir($location.'/'.$file)) {
             $stats = stat($location.'/'.$file);
             $total += $stats['size'];
             unset($file);
          }
       }
       closedir($all);
       unset($all);
       return $total;
  }

/**
 * format unit of size
 * Author:   Darian Brown
 * Date:     2011-06-06
 * Website:  http://www.darian-brown.com/get-and-display-hard-disk-space-usage-using-php/
 * @param int $bytes
 */
    function _formatSize( $bytes ){
        $types = array( 'B', 'KB', 'MB', 'GB', 'TB' );
        for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
        return( round( $bytes, 2 ) . " " . $types[$i] );
    }

/**
 * Render table row for a template
 * @param Doku_Renderer $renderer
 * @param string $head
 * @param string $cell
 */
    function _row(&$renderer,$head,$cell,$strong){
        if(empty($cell))return;
        $renderer->tablerow_open();
        $renderer->tablecell_open();
        $renderer->doc.=$head;
        $renderer->tablecell_close();
        $renderer->tablecell_open();
        if($strong) $renderer->strong_open();
        $renderer->doc.=$cell;
        if($strong) $renderer->strong_close();
        $renderer->tablecell_close();
        $renderer->tablerow_close();
    }
/**
 * table name row for a template
 * @param Doku_Renderer $renderer
 * @param string $cid
 * @param string $iupac
 * @param string $title
 */
    function _nssize_header(&$renderer,$name,$size){
        $renderer->tablerow_open();
        $renderer->tableheader_open();
        $renderer->doc.=$name;
        $renderer->tableheader_close();
        $renderer->tableheader_open();
        $renderer->doc.=$size;
        $renderer->tableheader_close();
        $renderer->tablerow_close();
    }
}
?>