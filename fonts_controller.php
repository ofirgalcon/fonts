<?php 

/**
 * Fonts module class
 *
 * @package munkireport
 * @author tuxudo
 **/
class Fonts_controller extends Module_controller
{
    private $allowed_scrollbox_columns = array('type_name', 'vendor', 'type');
    private $allowed_button_columns = array('type_enabled', 'valid', 'duplicate', 'copy_protected');

    /*** Protect methods with auth! ****/
    function __construct()
    {
        // Store module path
        $this->module_path = dirname(__FILE__);
    }

    /**
     * Default method
     * @author tuxudo
     *
     **/
    function index()
    {
        echo "You've loaded the fonts module!";
    }

    /**
     * Get font names for widget
     *
     * @return void
     * @author tuxudo
     **/
     public function get_fonts()
     {
        $obj = new View();

        if (! $this->authorized()) {
            $obj->view('json', array('msg' => array('error' => 'Not authenticated')));
            return;
        }
        
        $fonts = new Fonts_model();
        $obj->view('json', array('msg' => $fonts->get_fonts()));
     }

    /**
     * Get font vendors for widget
     *
     * @return void
     * @author tuxudo
     **/
     public function get_vendor()
     {
        $obj = new View();

        if (! $this->authorized()) {
            $obj->view('json', array('msg' => array('error' => 'Not authenticated')));
            return;
        }
        
        $fonts = new Fonts_model();
        $obj->view('json', array('msg' => $fonts->get_vendor()));
     }

    /**
     * Get font types for widget
     *
     * @return void
     * @author tuxudo
     **/
     public function get_type()
     {
        $obj = new View();

        if (! $this->authorized()) {
            $obj->view('json', array('msg' => array('error' => 'Not authenticated')));
            return;
        }
        
        $fonts = new Fonts_model();
        $obj->view('json', array('msg' => $fonts->get_type()));
     }

    /**
     * Get data for scrollbox widgets
     *
     * @return void  
     * @author tuxudo
     **/
    public function get_list($column)
    {
        if (! $this->authorized()) {
            jsonView(array('error' => 'Not authenticated'));
            return;
        }

        $column = $this->sanitize_column($column, $this->allowed_scrollbox_columns);
        if (! $column) {
            jsonView(array('error' => 'Invalid column'));
            return;
        }

        $fonts = new Fonts_model();
        $sql = "SELECT COUNT(CASE WHEN `".$column."` <> '' AND `".$column."` IS NOT NULL THEN 1 END) AS count, `".$column."` AS label
                FROM fonts
                LEFT JOIN reportdata USING (serial_number)
                ".get_machine_group_filter()."
                GROUP BY `".$column."`
                ORDER BY count DESC";

        $out = [];
        foreach ($fonts->query($sql) as $obj) {
            if ("$obj->count" !== "0") {
                $obj->label = $obj->label ? $obj->label : 'Unknown';
                $out[] = $obj;
            }
        }

        jsonView($out);
    }

    /**
     * Get data for button widget
     *
     * @return void
     * @author tuxudo
     **/
    public function get_button_widget($column)
    {
        if (! $this->authorized()) {
            jsonView(array('error' => 'Not authenticated'));
            return;
        }

        $column = $this->sanitize_column($column, $this->allowed_button_columns);
        if (! $column) {
            jsonView(array('error' => 'Invalid column'));
            return;
        }

        $sql = "SELECT COUNT(CASE WHEN `".$column."` = '1' THEN 1 END) AS 'yes',
                    COUNT(CASE WHEN `".$column."` = '0' THEN 1 END) AS 'no'
                    FROM fonts
                    LEFT JOIN reportdata USING (serial_number)
                    WHERE ".get_machine_group_filter('');

        $out = [];
        $queryobj = new Fonts_model();
        $result = $queryobj->query($sql);
        if ($result && count($result) > 0 && is_object($result[0])) {
            foreach((array)$result[0] as $label => $value){
                    $out[] = ['label' => $label, 'count' => $value];
            }
        }

        jsonView($out);
    }

    private function sanitize_column($column, $allowed_columns)
    {
        $column = preg_replace("/[^A-Za-z0-9_\-]/", '', (string) $column);
        if (! in_array($column, $allowed_columns, true)) {
            return '';
        }

        return $column;
    }

    /**
     * Retrieve data in json format
     *
     **/
     public function get_data($serial_number = '')
     {
        $obj = new View();

        if (! $this->authorized()) {
            $obj->view('json', array('msg' => 'Not authorized'));
            return;
        }

        $queryobj = new Fonts_model();
        $fonts_tab = array();
        
        // Fix null safety issue to prevent crashes
        $records = $queryobj->retrieve_records($serial_number);
        if ($records) {
            foreach($records as $fontEntry) {
                $fonts_tab[] = $fontEntry->rs;
            }
        }

        $obj->view('json', array('msg' => $fonts_tab));
     }

} // END class Fonts_controller
