<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * CodeIgniter PDF Library
 *
 * Generate PDF in CodeIgniter applications.
 *
 * @package            CodeIgniter
 * @subpackage        Libraries
 * @category        Libraries
 * @author            CodexWorld
 * @license            https://www.codexworld.com/license/
 * @link            https://www.codexworld.com
 */

// reference the Dompdf namespace
use Dompdf\Dompdf;

class Pdf
{
    public function __construct(){
        // Dompdf is installed and autoloaded by Composer.
        $pdf = new Dompdf();
        
        $CI =& get_instance();
        $CI->dompdf = $pdf;
        
    }
}
?>
