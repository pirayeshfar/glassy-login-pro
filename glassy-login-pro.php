<?php
/**
 * Plugin Name: Glassy Login Pro + Hide Login URL
 * Plugin URI:  https://home-visa.ir
 * Description: صفحه ورود شیشه‌ای حرفه‌ای با لوگو، بک‌گراند و ویدیو از گالری + حذف کامل زبان و لینک‌های اضافی
 * Version: 2.4
 * Author: امیرسامان پیرایش‌فر
 * Author URI: https://home-visa.ir
 */

if (!defined('ABSPATH')) exit;

class GlassyLoginPro {

    private $login_slug;

    public function __construct() {
        $this->login_slug = trim(get_option('glassy_custom_login_slug', 'login'));

        add_action('init', [$this, 'block_default_logins'], 1);
        add_action('init', [$this, 'add_custom_rewrite_rules']);
        add_filter('login_url', [$this, 'custom_login_url'], 10, 3);
        add_filter('lostpassword_url', [$this, 'custom_login_url'], 10, 3);
        add_filter('register_url', [$this, 'custom_register_url']);

        add_action('login_enqueue_scripts', [$this, 'login_styles']);
        add_action('login_header', [$this, 'custom_login_logo']);

        // حذف کامل زبان، لینک بازیابی رمز و بخش‌های اضافی
        remove_action('login_footer', 'wp_login_language_switch');
        add_filter('login_message', '__return_empty_string');
        add_filter('gettext', [$this, 'remove_lostpassword_text'], 20, 3);
        add_action('login_form', [$this, 'hide_extra_elements']);

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
        add_action('admin_footer', [$this, 'media_uploader_script']);
    }

    public function media_uploader_script() {
        ?>
        <script>
        jQuery(document).ready(function($){
            function initUploader(buttonId, inputId, previewId) {
                $('#'+buttonId).on('click', function(e) {
                    e.preventDefault();
                    var mediaUploader = wp.media({
                        title: 'انتخاب فایل',
                        button: { text: 'استفاده از این فایل' },
                        multiple: false
                    });
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#'+inputId).val(attachment.url);
                        if (previewId) $('#'+previewId).attr('src', attachment.url).show();
                    });
                    mediaUploader.open();
                });
            }
            initUploader('upload_logo_button', 'glassy_custom_logo', 'logo_preview');
            initUploader('upload_bg_button', 'glassy_bg_url', 'bg_preview');
            initUploader('upload_video_button', 'glassy_video_url', null); // بدون پیش‌نمایش برای ویدیو
        });
        </script>
        <?php
    }

    public function custom_login_logo() {
        $logo = get_option('glassy_custom_logo');
        if ($logo) {
            echo '<div style="text-align:center;margin-bottom:30px;"><img src="'.esc_url($logo).'" alt="Home Visa" style="max-height:110px;width:auto;"></div>';
        }
    }

    public function hide_extra_elements() {
        echo '<style>#nav,#backtoblog,.privacy-policy-link,.language-switcher,#login form p:last-of-type{display:none!important}</style>';
    }

    public function remove_lostpassword_text($translated_text, $text, $domain) {
        if (in_array($text, ['رمز عبورتان را گم کرده‌اید؟', 'Lost your password?'])) return '';
        return $translated_text;
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

    public function block_default_logins() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, 'wp-login.php') !== false || 
            (strpos($request_uri, '/wp-admin') !== false && !is_user_logged_in())) {
            wp_die('دسترسی ممنوع است.', 403);
        }
    }

    public function add_custom_rewrite_rules() {
        add_rewrite_rule($this->login_slug.'/?$', 'wp-login.php', 'top');
    }

    public function custom_login_url($url) { return home_url('/' . $this->login_slug); }
    public function custom_register_url($url) { return home_url('/' . $this->login_slug . '/register'); }

    public function login_styles() {
        $bg = get_option('glassy_bg_url');
        $video = get_option('glassy_video_url');
        ?>
        <style type="text/css">
            body.login{background:#000;position:relative;overflow:hidden}
            <?php if ($video): ?>
            video#bgvid{position:fixed;top:50%;left:50%;min-width:100%;min-height:100%;width:auto;height:auto;z-index:-3;transform:translateX(-50%) translateY(-50%)}
            <?php endif; ?>
            body.login::before{content:'';position:fixed;top:0;left:0;right:0;bottom:0;background:url('<?php echo esc_url($bg); ?>') center/cover no-repeat;z-index:-2}
            body.login::after{content:'';position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,10,40,0.70);z-index:-1}
            #login{padding:5% 0 0;max-width:400px}
            .login h1 a{display:none!important}
            .login form{background:rgba(255,255,255,0.14)!important;backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border:1px solid rgba(255,255,255,0.2);border-radius:20px;box-shadow:0 15px 35px rgba(0,0,0,0.4);padding:45px}
            .login label{color:#fff;font-weight:600;font-size:15px}
            .login input[type=text],.login input[type=password]{background:rgba(255,255,255,0.18)!important;border:none!important;color:#fff!important;border-radius:12px;padding:14px;font-size:15px}
            #wp-submit{background:#007cba!important;border:none!important;border-radius:12px!important;padding:14px 40px!important;font-weight:bold;font-size:16px}
            #wp-submit:hover{background:#005a87!important}
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
        $wpdb->insert($wpdb->prefix.'glassy_login_logs', [
            'username' => sanitize_text_field($username),
            'ip' => $ip,
            'status' => $status,
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
            echo '<div class="updated"><p>قوانین بازنویسی بروز شد.</p></div>';
        }
        $logo = get_option('glassy_custom_logo');
        $bg = get_option('glassy_bg_url');
        ?>
        <div class="wrap">
            <h1>Glassy Login Pro – تنظیمات نهایی (نسخه ۲.۴)</h1>
            <form method="post" action="options.php">
                <?php settings_fields('glassy_login_options'); ?>
                <table class="form-table">
                    <tr><th>آدرس ورود (مثال: visa-login)</th><td><input type="text" name="glassy_custom_login_slug" value="<?php echo esc_attr($this->login_slug); ?>" required> → <strong><?php echo home_url('/'.$this->login_slug); ?></strong></td></tr>
                    <tr><th>لوگو اصلی</th><td><input type="text" id="glassy_custom_logo" name="glassy_custom_logo" value="<?php echo esc_attr($logo); ?>" readonly> <input type="button" id="upload_logo_button" class="button" value="انتخاب از گالری"><?php if($logo): ?><br><img id="logo_preview" src="<?php echo esc_url($logo); ?>" style="max-height:100px;margin-top:10px;"><?php endif; ?></td></tr>
                    <tr><th>بک‌گراند تصویر</th><td><input type="text" id="glassy_bg_url" name="glassy_bg_url" value="<?php echo esc_attr($bg); ?>" readonly> <input type="button" id="upload_bg_button" class="button" value="انتخاب از گالری"><?php if($bg): ?><br><img id="bg_preview" src="<?php echo esc_url($bg); ?>" style="max-width:300px;margin-top:10px;"><?php endif; ?></td></tr>
                    <tr><th>بک‌گراند ویدیو (mp4)</th><td><input type="text" id="glassy_video_url" name="glassy_video_url" value="<?php echo esc_attr(get_option('glassy_video_url')); ?>" readonly> <input type="button" id="upload_video_button" class="button" value="انتخاب از گالری"></td></tr>
                </table>
                <?php submit_button('ذخیره تغییرات'); ?>
            </form>
            <form method="post"><input type="hidden" name="flush_rules" value="1"><?php submit_button('بروزرسانی قوانین آدرس', 'secondary'); ?></form>
        </div>
        <?php
    }
}

new GlassyLoginPro();