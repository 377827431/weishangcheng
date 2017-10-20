<?php 
namespace H5\Active;

use Common\Common\CommonController;
class Active extends CommonController{
    protected $active;
    private $tplDir = '';
    
    function __construct($active){
        parent::__construct();
        $this->active = $active;
        $this->tplDir = 'Active/'.$active['id'].'/';
    }
    
    public function display($tplName = ACTION_NAME){
        parent::display($this->tplDir.$tplName);
    }
}
?>