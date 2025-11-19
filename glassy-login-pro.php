<?php
/**
 * Plugin Name: Glassy Login Pro + Hide Login URL
 * Plugin URI: https://cafegardee.ir
 * Description: صفحه ورود شیشه‌ای حرفه‌ای با لوگوی سفارشی، دکبه ورود بزرگ و امنیت بالا
 * Version: 3.3
 * Author: امیرسامان پیرایش‌فر
 * Author URI: https://cafegardee.ir
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class GlassyLoginPro {
    private $login_slug;

    public function __construct() {
        $this->login_slug = trim(get_option('glassy_custom_login_slug', 'visa'));

        add_action('init', [$this, 'block_default_logins'], 1);
        add_action('init', [$this, 'add_custom_rewrite_rules']);
        add_filter('login_url', [$this, 'custom_login_url'], 10, 3);
        add_filter('lostpassword_url', [$this, 'custom_login_url'], 10, 3);
        add_filter('register_url', [$this, 'custom_register_url']);

        add_action('login_enqueue_scripts', [$this, 'login_styles'], 999);
        add_action('login_header', [$this, 'custom_logo_only'], 1);

        remove_action('login_footer', 'wp_login_language_switch');
        add_filter('login_message', '__return_empty_string');
        add_filter('gettext', [$this, 'remove_lostpassword_text'], 20, 3);
        add_action('login_form', [$this, 'force_remember_and_submit']);

        add_action('wp_login', [$this, 'log_success'], 10, 2);
        add_action('wp_login_failed', [$this, 'log_failed']);

        add_action('admin_menu', [$this, 'admin_menu']);
        register_activation_hook(__FILE__, [$this, 'create_table']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_media_uploader']);
    }

    public function enqueue_media_uploader($hook) {
        if ('settings_page_glassy-login-pro' !== $hook) return;
        wp_enqueue_media();
        wp_add_inline_script('jquery', '
            jQuery(function($){
                function uploader(btn, input, prev) {
                    $("#"+btn).on("click", function(e){
                        e.preventDefault();
                        var u = wp.media({title:"انتخاب فایل",button:{text:"استفاده"},multiple:false});
                        u.on("select", function(){
                            var url = u.state().get("selection").first().toJSON().url;
                            $("#"+input).val(url);
                            if(prev) $("#"+prev).attr("src",url).show();
                        });
                        u.open();
                    });
                }
                uploader("upload_logo_button", "glassy_custom_logo", "logo_preview");
                uploader("upload_bg_button", "glassy_bg_url", "bg_preview");
                uploader("upload_video_button", "glassy_video_url", null);
            });
        ');
    }

    // لوگو خیلی نزدیک به فرم (فقط 15 پیکسل فاصله)
    public function custom_logo_only() {
        $logo = get_option('glassy_custom_logo');
        if ($logo) {
            echo '<div style="text-align:center;margin-bottom:15px;"><img src="'.esc_url($logo).'" alt="لوگو سایت" style="max-height:90px;width:auto;"></div>';
        }
        echo '<style>.login h1{display:none!important}</style>';
    }

    public function force_remember_and_submit() {
        echo '<style>
            .forgetmenot {float:none !important;text-align:right;margin:20px 0 30px 0 !important;display:block}
            .forgetmenot label {color:#ddd;font-size:14px}
            #wp-submit {display:block!important;width:100%!important;height:55px!important;background:#0066cc!important;color:#fff!important;border:none!important;border-radius:14px!important;font-size:17px!important;font-weight:bold!important;box-shadow:0 6px 20px rgba(0,102,204,0.4);cursor:pointer;transition:0.3s}
            #wp-submit:hover {background:#0055aa!important;transform:translateY(-3px)}
            #nav,#backtoblog,.privacy-policy-link,.language-switcher{display:none!important}
        </style>';
    }

    public function remove_lostpassword_text($translated, $text) {
        if (in_array($text, ['رمز عبورتان را گم کرده‌اید؟', 'Lost your password?'])) return '';
        return $translated;
    }

    public function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'glassy_login_logs';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            username VARCHAR(255),
            ip VARCHAR(45),
            status ENUM('success','failed') DEFAULT 'success',
            time DATETIME DEFAULT CURRENT_TIMESTAMP,
            user_agent TEXT,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // بلاک فقط در حالت لاگین‌نشده + رفع مشکل ریدایرکت پس از لاگین
    public function block_default_logins() {
        if (is_user_logged_in()) return; // اجازه ریدایرکت پس از ورود

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, 'wp-login.php') !== false && !isset($_POST['wp-submit'])) {
            wp_die('دسترسی ممنوع است.', 403);
        }
    }

    public function add_custom_rewrite_rules() {
        add_rewrite_rule($this->login_slug . '/?$', 'wp-login.php', 'top');
        add_rewrite_rule($this->login_slug . '/register/?$', 'wp-login.php?action=register', 'top');
    }

    public function custom_login_url() { return home_url('/' . $this->login_slug); }
    public function custom_register_url() { return home_url('/' . $this->login_slug . '/register'); }

    public function login_styles() {
        $bg = get_option('glassy_bg_url');
        $video = get_option('glassy_video_url');
        ?>
        <style type="text/css">
            body.login{background:#000;overflow:hidden;position:relative}
            <?php if ($video): ?>
            video#bgvid{position:fixed;top:50%;left:50%;min-width:100%;min-height:100%;width:auto;height:auto;z-index:-3;transform:translate(-50%,-50%)}
            <?php endif; ?>
            body.login::before{content:'';position:fixed;top:0;left:0;right:0;bottom:0;background:url('<?php echo esc_url($bg); ?>') center/cover no-repeat;z-index:-2}
            body.login::after{content:'';position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,10,40,0.72);z-index:-1}
            #login{max-width:380px;margin:3% auto 0;padding:0} /* فاصله کلی کمتر شد تا لوگو نزدیک باشد */
            .login form{background:rgba(255,255,255,0.15)!important;backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.25);border-radius:24px;padding:50px 40px 40px;box-shadow:0 20px 40px rgba(0,0,0,0.5)}
            .login label{color:#fff;font-weight:600;font-size:15px;margin-bottom:8px;display:block}
            .login input[type=text], .login input[type=password]{background:rgba(255,255,255,0.2)!important;border:none!important;color:#fff!important;border-radius:12px;padding:15px;font-size:15px;width:100%;margin-bottom:16px;box-sizing:border-box}
        </style>
        <?php if ($video): ?>
        <video playsinline autoplay muted loop id="bgvid"><source src="<?php echo esc_url($video); ?>" type="video/mp4"></video>
        <?php endif; ?>
        <?php
    }

    public function log_success($user_login, $user) { $this->log($user_login, 'success'); }
    public function log_failed($username) { $this->log($username, 'failed'); }

    private function log($username, $status) {
        global $wpdb;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $wpdb->insert($wpdb->prefix . 'glassy_login_logs', [
            'username'   => sanitize_text_field($username),
            'ip'         => $ip,
            'status'     => $status,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    public function admin_menu() {
        add_options_page('Glassy Login Pro', 'Glassy Login Pro', 'manage_options', 'glassy-login-pro', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('glassy_login_options', 'glassy_custom_login_slug');
        register_setting('glassy_login_options', 'glassy_bg_url');
        register_setting('glassy_login_options', 'glassy_video_url');
        register_setting('glassy_login_options', 'glassy_custom_logo');
    }

    public function settings_page() {
        if (isset($_POST['flush_rules'])) {
            flush_rewrite_rules();
            echo '<div class="updated"><p>قوانین بازنویسی با موفقیت بروزرسانی شد.</p></div>';
        }
        $logo = get_option('glassy_custom_logo');
        $bg = get_option('glassy_bg_url');
        ?>
        <div class="wrap">
            <h1>Glassy Login Pro – نسخه ۳.۳ (نهایی)</h1>
            <form method="post" action="options.php">
                <?php settings_fields('glassy_login_options'); ?>
                <table class="form-table">
                    <tr><th>آدرس ورود</th><td><input type="text" name="glassy_custom_login_slug" value="<?php echo esc_attr($this->login_slug); ?>" required> → <strong><?php echo home_url('/' . $this->login_slug); ?></strong></td></tr>
                    <tr><th>لوگو سفارشی</th><td><input type="text" id="glassy_custom_logo" name="glassy_custom_logo" value="<?php echo esc_attr($logo); ?>" readonly> <input type="button" id="upload_logo_button" class="button" value="انتخاب از گالری"><?php if($logo): ?><br><img id="logo_preview" src="<?php echo esc_url($logo); ?>" style="max-height:100px;margin-top:10px"><?php endif; ?></td></tr>
                    <tr><th>بک‌گراند تصویر</th><td><input type="text" id="glassy_bg_url" name="glassy_bg_url" value="<?php echo esc_attr($bg); ?>" readonly> <input type="button" id="upload_bg_button" class="button" value="انتخاب از گالری"><?php if($bg): ?><br><img id="bg_preview" src="<?php echo esc_url($bg); ?>" style="max-width:300px;margin-top:10px"><?php endif; ?></td></tr>
                    <tr><th>ویدیو بک‌گراند</th><td><input type="text" id="glassy_video_url" name="glassy_video_url" value="<?php echo esc_attr(get_option('glassy_video_url')); ?>" readonly> <input type="button" id="upload_video_button" class="button" value="انتخاب از گالری"></td></tr>
                </table>
                <?php submit_button('ذخیره تغییرات'); ?>
            </form>
            <form method="post"><input type="hidden" name="flush_rules" value="1"><?php submit_button('بروزرسانی قوانین آدرس', 'secondary'); ?></form>
        </div>
        <?php
    }
}

new GlassyLoginPro();
