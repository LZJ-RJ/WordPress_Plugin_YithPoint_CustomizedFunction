<?php

class WordPressTheme
{
    private static $instance;

    public static function get_instance()
    {
        if (is_null(self::$instance))
            self::$instance = new self;

        return self::$instance;
    }

    public function __construct()
    {
        $this->register_hooks();
    }

    private function register_hooks()
    {
        add_filter('ywpar_show_admin_tabs', [$this, 'add_tab_for_birth_bonus']); //在Yith Point外掛裡面新增一個頁籤
        add_action('wp_loaded', [$this, 'set_cron_yith_birth_bonus']); //觸發排程
        add_action('ywpar_birth_bonus', [$this, 'add_birth_bonus']); //排程內容
        add_action('after_display_name_profile', [$this, 'add_admin_profile']); //後臺使用者介面新增生日欄位
        add_action('profile_update', [$this, 'update_user_profile']); //後台更新WordPress使用者的資訊「生日」
        add_action('woocommerce_save_account_details', [$this, 'update_edit_user_birth']); //前台WooCommerce會員中心更新使用者資訊「生日」
    }

    public function add_tab_for_birth_bonus($tabs)
    {
//        可以再修改程可翻譯的文字，'生日紅利' => __('生日紅利', 'text_domain')
        $tabs['birth-bonus'] = '生日紅利';
        return $tabs;
    }

    public function set_cron_yith_birth_bonus()
    {
        $birth_option_activate = YITH_WC_Points_Rewards()->get_option('birth_activate');
        if ( !wp_next_scheduled( 'ywpar_birth_bonus' ) && $birth_option_activate == 'yes' )
        {
            $current_time = new DateTime('now');
            $current_time_format = $current_time->format('Y-m-d');
            $current_time_format = explode('-', $current_time_format);
            $current_time_format[2] = '1';
            $current_time_format = implode('-', $current_time_format);
            $current_time = new DateTime($current_time_format);
            $current_time = $current_time->modify('+1 month');
            $next_date = $current_time->getTimestamp();
            wp_schedule_single_event( $next_date, 'ywpar_birth_bonus' );
        }else if( $birth_option_activate != 'yes'){
            wp_clear_scheduled_hook( 'ywpar_birth_bonus' );
        }
    }

    public function add_birth_bonus()
    {
        $current_time = new DateTime('now');
        $current_day = $current_time->format('d');
        $current_month = $current_time->format('m');
        $birth_bonus_option = YITH_WC_Points_Rewards()->get_option('birth_bonus');
        $birth_option_activate = YITH_WC_Points_Rewards()->get_option('birth_activate');
        if( is_numeric($birth_bonus_option) && $current_day == 1 && $birth_option_activate == 'yes' )
        {
            $args = array(
                'meta_query' => array(
                    array(
                        'key'     => 'user_birth_m',
                        'value'   => $current_month,
                        'compare' => '='
                    ),
                ));
            $users = get_users($args);
            foreach ($users as $user )
            {
                if(get_user_meta($user->ID, 'is_checked_point',1) != 'true')
                {
                    YITH_WC_Points_Rewards_Earning()->add_points($user->ID, $birth_bonus_option, 'admin_action', '');
                    update_user_meta($user->ID, 'is_checked_point' , 'true');
                }
            }
            $current_time = new DateTime('now');
            $current_time_format = $current_time->format('Y-m-d');
            $current_time_format = explode('-', $current_time_format);
            $current_time_format[2] = '1';
            $current_time_format = implode('-', $current_time_format);
            $current_time = new DateTime($current_time_format);
            $current_time = $current_time->modify('+1 month');
            $next_date = $current_time->getTimestamp();
            wp_schedule_single_event( $next_date, 'ywpar_birth_bonus' );
        }else if( $birth_option_activate != 'yes' ){
            wp_clear_scheduled_hook( 'ywpar_birth_bonus' );
        }
    }

    public function add_admin_profile($user_id)
    {
        if($_GET['user_id'] != '')
        {
            $user_id = $_GET['user_id'];
        }else{
            $user_id = get_current_user_id();
        }
        ?>
        <script>
            jQuery(function ($) {
                $('#user_birth').datepicker();
            });
        </script>
        <tr class="user-birth-wrap">
            <th><label for="user_birth">生日</label></th>
            <td><input type="text" name="user_birth" id="user_birth" value="<?=(get_user_meta($user_id,'user_birth',1))?>" class="regular-text"></td>
        </tr>
        <?php
    }

    public function update_user_profile($user_id)
    {
        if( $_POST['user_birth'] != '')
        {
            update_user_meta($user_id, 'user_birth', $_POST['user_birth']);
            $birth_m = explode('-', $_POST['user_birth'])[1];
            update_user_meta($user_id, 'user_birth_m', $birth_m);
        }
    }

    public function update_edit_user_birth()
    {
        $user_id = get_current_user_id();
        if( $_POST['account_birth'] != '' && !get_user_meta($user_id, 'user_birth_m',1) )
        {
            update_user_meta($user_id, 'user_birth', $_POST['account_birth']);
            $birth_m = explode('-', $_POST['account_birth'])[1];
            update_user_meta($user_id, 'user_birth_m', $birth_m);
        }
    }
}

$GLOBALS['WordPressTheme'] = WordPressTheme::get_instance();
