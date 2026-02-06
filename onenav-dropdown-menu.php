<?php
/**
 * Plugin Name: Onenav Pro Dropdown Menu (Customizable)
 * Plugin URI: https://www.ywdjdh.com/
 * Description: 为 OneNav 主题定制的增强版下拉菜单插件。支持在主题设置中自由添加、修改下拉分类及其对应的子网址。整合了毛玻璃 UI 与动态数据读取。支持从主题图标库选择图标或上传自定义图标。
 * Version: 1.4.0
 * Author: 一网打尽导航
 * Author URI: https://www.ywdjdh.com
 * License: GPL v2 or later
 * Text Domain: onenav-pro-dropdown
 */

// 防止直接访问
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OnenavProDropdown {
    
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // 插件激活时设置默认值
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        // 初始化
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * 插件激活
     */
    public function activate() {
        // 检查是否安装了Onenav主题
        if ( ! $this->is_onenav_theme_active() ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( '此插件需要Onenav主题才能正常工作。请先安装并激活Onenav主题。' );
        }
        
        // 设置默认选项
        $this->set_default_options();
    }

    /**
     * 检查Onenav主题是否激活
     */
    private function is_onenav_theme_active() {
        $theme = wp_get_theme();
        return ( 'OneNav' === $theme->name || 'OneNav' === $theme->parent_theme );
    }

    /**
     * 设置默认选项
     */
    private function set_default_options() {
        $options = get_option( 'io_get_option', array() );
        
        // 如果还没有设置过，添加默认值
        if ( ! isset( $options['opd_enabled'] ) ) {
            $options['opd_enabled'] = true;
        }
        
        if ( ! isset( $options['opd_menus'] ) || empty( $options['opd_menus'] ) ) {
            $options['opd_menus'] = array(
                array(
                    'title' => '邮箱',
                    'icon_type' => 'theme',
                    'icon' => 'iconfont icon-xiaoxi',
                    'items' => array(
                        array(
                            'title' => 'Gmail',
                            'url' => 'https://mail.google.com',
                            'target' => '_blank',
                            'tooltip' => '访问Gmail邮箱服务'
                        ),
                        array(
                            'title' => 'Outlook',
                            'url' => 'https://outlook.live.com',
                            'target' => '_blank',
                            'tooltip' => '访问Microsoft Outlook邮箱'
                        ),
                        array(
                            'title' => 'QQ邮箱',
                            'url' => 'https://mail.qq.com',
                            'target' => '_blank',
                            'tooltip' => '访问QQ邮箱服务'
                        ),
                    )
                ),
                array(
                    'title' => '网盘',
                    'icon_type' => 'theme',
                    'icon' => 'iconfont icon-collection',
                    'items' => array(
                        array(
                            'title' => '百度网盘',
                            'url' => 'https://pan.baidu.com',
                            'target' => '_blank',
                            'tooltip' => '访问百度网盘云存储服务'
                        ),
                        array(
                            'title' => '阿里云盘',
                            'url' => 'https://www.aliyundrive.com',
                            'target' => '_blank',
                            'tooltip' => '访问阿里云盘云存储服务'
                        ),
                        array(
                            'title' => 'OneDrive',
                            'url' => 'https://onedrive.live.com',
                            'target' => '_blank',
                            'tooltip' => '访问Microsoft OneDrive云存储'
                        ),
                    )
                ),
                array(
                    'title' => '地图',
                    'icon_type' => 'theme',
                    'icon' => 'iconfont icon-dagou',
                    'items' => array(
                        array(
                            'title' => '百度地图',
                            'url' => 'https://map.baidu.com',
                            'target' => '_blank',
                            'tooltip' => '访问百度地图导航服务'
                        ),
                        array(
                            'title' => '高德地图',
                            'url' => 'https://www.amap.com',
                            'target' => '_blank',
                            'tooltip' => '访问高德地图导航服务'
                        ),
                        array(
                            'title' => 'Google地图',
                            'url' => 'https://maps.google.com',
                            'target' => '_blank',
                            'tooltip' => '访问Google地图服务'
                        ),
                    )
                ),
            );
            update_option( 'io_get_option', $options );
        }
    }

    public function init() {
        // 检查主题是否为 OneNav
        if ( ! $this->is_onenav_theme_active() ) {
            return;
        }

        // 添加功能
        $this->add_features();
    }

    /**
     * 添加功能
     */
    private function add_features() {
        // 前端功能
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 99 );
        add_action( 'wp_head', array( $this, 'add_inline_styles' ), 99 );
        add_action( 'io_header_after_code', array( $this, 'render_dropdown_menu' ), 15 );
        
        // 后端设置选项 - 使用多个 hook 确保加载
        add_action( 'io_setting_option_after_code', array( $this, 'add_theme_settings' ), 20 );
        // 备用方案：使用 admin_menu 确保在菜单加载后添加
        add_action( 'admin_menu', array( $this, 'add_theme_settings_admin' ), 99 );
    }

    /**
     * 渲染前端 CSS 样式（含毛玻璃效果）
     */
    public function enqueue_styles() {
        $css = $this->get_custom_css();
        // 尝试添加到主样式表
        if ( wp_style_is( 'main', 'enqueued' ) ) {
            wp_add_inline_style( 'main', $css );
        }
    }

    /**
     * 直接在 head 中添加样式（备用方案）
     */
    public function add_inline_styles() {
        // 如果上面的方法没有生效，直接在head中输出
        if ( ! wp_style_is( 'main', 'done' ) ) {
            $css = $this->get_custom_css();
            echo '<style id="onenav-dropdown-menu-styles">' . $css . '</style>';
        }
    }

    /**
     * 获取自定义CSS
     */
    private function get_custom_css() {
        return "
        /* 下拉菜单容器 */
        .opd-menu-container { 
            display: flex !important; 
            align-items: center !important; 
            gap: 8px !important; 
            margin-right: 15px !important; 
            flex-wrap: nowrap !important;
        }
        
        /* 下拉菜单项 */
        .opd-dropdown { 
            position: relative !important; 
            list-style: none !important; 
            padding: 0 !important; 
            margin: 0 !important; 
            display: inline-block !important;
        }
        
        /* 下拉按钮 */
        .opd-drop-btn { 
            display: flex !important; 
            align-items: center !important; 
            padding: 8px 14px !important; 
            color: var(--text-color, #333) !important; 
            font-size: 14px !important; 
            text-decoration: none !important; 
            transition: all 0.3s ease !important; 
            cursor: pointer !important; 
            border-radius: 6px !important;
            white-space: nowrap !important;
            border: 1px solid transparent !important;
        }
        .opd-drop-btn:hover { 
            background: rgba(255,255,255,0.1) !important; 
            color: var(--primary-color, #0073aa) !important; 
            border-color: rgba(255,255,255,0.2) !important;
        }
        .opd-drop-btn i { 
            margin-right: 6px !important; 
            font-size: 16px !important; 
        }
        .opd-drop-btn .opd-custom-icon {
            width: 16px !important;
            height: 16px !important;
            margin-right: 6px !important;
            object-fit: contain !important;
            vertical-align: middle !important;
            display: inline-block !important;
        }

        /* 下拉面板样式 - 关键：确保垂直显示 */
        .opd-dropdown .opd-sub-menu { 
            position: absolute !important; 
            top: 100% !important; 
            left: 0 !important; 
            min-width: 180px !important;
            max-width: 250px !important;
            background: rgba(255, 255, 255, 0.95) !important; 
            backdrop-filter: blur(10px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(10px) saturate(180%) !important;
            border-radius: 8px !important; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.12) !important;
            padding: 6px 0 !important; 
            margin-top: 8px !important;
            opacity: 0 !important; 
            visibility: hidden !important;
            transform: translateY(-10px) scale(0.95) !important; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important; 
            z-index: 99999 !important;
            border: 1px solid rgba(255,255,255,0.3) !important;
            display: block !important;
            list-style: none !important;
            margin: 0 !important;
        }
        .opd-dropdown:hover .opd-sub-menu,
        .opd-dropdown .opd-sub-menu:hover { 
            opacity: 1 !important; 
            visibility: visible !important; 
            transform: translateY(0) scale(1) !important; 
        }
        
        /* 下拉菜单项 - 确保垂直排列 */
        .opd-dropdown .opd-sub-menu .opd-sub-item { 
            list-style: none !important; 
            margin: 0 !important; 
            padding: 0 !important;
            display: block !important;
            width: 100% !important;
            float: none !important;
        }
        .opd-dropdown .opd-sub-menu .opd-sub-item a { 
            display: block !important; 
            padding: 10px 18px !important; 
            color: #333 !important; 
            text-decoration: none !important; 
            font-size: 13px !important; 
            transition: all 0.2s ease !important; 
            position: relative !important;
            width: 100% !important;
            box-sizing: border-box !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        .opd-dropdown .opd-sub-menu .opd-sub-item a:hover { 
            background: rgba(0,0,0,0.05) !important; 
            color: var(--primary-color, #0073aa) !important; 
            padding-left: 22px !important; 
        }
        .opd-dropdown .opd-sub-menu .opd-sub-item:first-child a {
            border-radius: 8px 8px 0 0 !important;
        }
        .opd-dropdown .opd-sub-menu .opd-sub-item:last-child a {
            border-radius: 0 0 8px 8px !important;
        }

        /* 暗色模式适配 */
        .io-black-mode .opd-dropdown .opd-sub-menu { 
            background: rgba(30, 30, 30, 0.95) !important; 
            border-color: rgba(255,255,255,0.15) !important; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.3) !important;
        }
        .io-black-mode .opd-dropdown .opd-sub-menu .opd-sub-item a { 
            color: #e0e0e0 !important; 
        }
        .io-black-mode .opd-dropdown .opd-sub-menu .opd-sub-item a:hover { 
            background: rgba(255,255,255,0.08) !important; 
            color: var(--primary-color, #4a9eff) !important;
        }
        .io-black-mode .opd-drop-btn {
            color: var(--text-color, #e0e0e0) !important;
        }
        .io-black-mode .opd-drop-btn:hover {
            background: rgba(255,255,255,0.08) !important;
            border-color: rgba(255,255,255,0.15) !important;
        }

        /* 移动端适配 */
        @media (max-width: 768px) { 
            .opd-menu-container { 
                display: none !important; 
            } 
        }
        
        /* 确保下拉菜单在顶部导航栏中正确对齐 */
        .header-nav .opd-menu-container,
        .main-header .opd-menu-container,
        .io-header .opd-menu-container {
            margin-left: auto !important;
        }
        ";
    }

    /**
     * 前端 HTML 输出
     */
    public function render_dropdown_menu() {
        // 检查是否启用
        $enabled = io_get_option( 'opd_enabled', true );
        if ( ! $enabled ) {
            return;
        }

        // 只在首页显示（可根据需要修改）
        if ( ! is_front_page() && ! is_home() ) {
            return;
        }

        // 获取菜单配置
        $menus = io_get_option( 'opd_menus', array() );
        if ( empty( $menus ) || ! is_array( $menus ) ) {
            return;
        }

        // 过滤掉无效的菜单项
        $valid_menus = array();
        foreach ( $menus as $menu ) {
            if ( ! empty( $menu['title'] ) ) {
                $valid_menus[] = $menu;
            }
        }

        if ( empty( $valid_menus ) ) {
            return;
        }

        // 输出HTML
        echo '<div class="opd-menu-container d-none d-md-flex">';
        foreach ( $valid_menus as $menu ) {
            $title = isset( $menu['title'] ) ? $menu['title'] : '';
            $icon_type = isset( $menu['icon_type'] ) ? $menu['icon_type'] : 'theme';
            $icon = isset( $menu['icon'] ) ? $menu['icon'] : 'iconfont icon-version';
            $icon_custom = isset( $menu['icon_custom'] ) ? $menu['icon_custom'] : '';
            $items = isset( $menu['items'] ) && is_array( $menu['items'] ) ? $menu['items'] : array();

            echo '<div class="opd-dropdown">';
            echo '<a class="opd-drop-btn" href="javascript:void(0);">';
            
            // 根据图标类型显示不同的图标
            if ( $icon_type === 'custom' && ! empty( $icon_custom ) ) {
                // 自定义图标（图片）
                echo '<img src="' . esc_url( $icon_custom ) . '" alt="' . esc_attr( $title ) . '" class="opd-custom-icon" />';
            } else {
                // 主题图标（iconfont）- 图标库返回的是完整类名
                $icon_class = ! empty( $icon ) ? esc_attr( $icon ) : 'iconfont icon-version';
                // 兼容处理：如果图标类名不包含 iconfont，则添加（兼容旧数据）
                if ( strpos( $icon_class, 'iconfont' ) === false && strpos( $icon_class, 'fa' ) === false ) {
                    $icon_class = 'iconfont ' . $icon_class;
                }
                echo '<i class="' . $icon_class . '"></i>';
            }
            
            echo '<span>' . esc_html( $title ) . '</span>';
            echo '</a>';
            
            if ( ! empty( $items ) ) {
                echo '<ul class="opd-sub-menu">';
                foreach ( $items as $item ) {
                    $item_title = isset( $item['title'] ) ? $item['title'] : '';
                    $item_url = isset( $item['url'] ) ? $item['url'] : '#';
                    $item_target = isset( $item['target'] ) ? $item['target'] : '_blank';
                    $item_tooltip = isset( $item['tooltip'] ) ? trim( $item['tooltip'] ) : '';
                    
                    // 验证URL格式
                    if ( ! empty( $item_title ) && ! empty( $item_url ) && filter_var( $item_url, FILTER_VALIDATE_URL ) ) {
                        echo '<li class="opd-sub-item">';
                        
                        // 构建链接属性
                        $link_attrs = array(
                            'href' => esc_url( $item_url ),
                            'target' => esc_attr( $item_target ),
                        );
                        
                        // 添加悬停说明（tooltip）
                        if ( ! empty( $item_tooltip ) ) {
                            $link_attrs['title'] = esc_attr( $item_tooltip );
                        } else {
                            // 如果没有设置tooltip，使用网址名称作为默认tooltip
                            $link_attrs['title'] = esc_attr( $item_title );
                        }
                        
                        // 如果是新标签打开，添加安全属性
                        if ( $item_target === '_blank' ) {
                            $link_attrs['rel'] = 'noopener noreferrer';
                        }
                        
                        $link_attr_string = '';
                        foreach ( $link_attrs as $attr => $value ) {
                            $link_attr_string .= ' ' . $attr . '="' . $value . '"';
                        }
                        
                        echo '<a' . $link_attr_string . '>';
                        echo esc_html( $item_title );
                        echo '</a>';
                        echo '</li>';
                    }
                }
                echo '</ul>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * 注入主题设置选项 (Codestar Framework)
     */
    public function add_theme_settings() {
        $this->register_theme_settings();
    }

    /**
     * Admin menu 时添加设置选项（备用方案）
     */
    public function add_theme_settings_admin() {
        // 确保 IOCF 类已加载
        if ( ! class_exists( 'IOCF' ) ) {
            return;
        }
        
        // 检查是否已经添加过
        static $added = false;
        if ( $added ) {
            return;
        }
        
        // 尝试添加设置选项
        $this->register_theme_settings();
        $added = true;
    }

    /**
     * 注册主题设置选项
     */
    private function register_theme_settings() {
        if ( ! class_exists( 'IOCF' ) ) {
            return;
        }

        IOCF::createSection( 'io_get_option', array(
            'title'  => '增强版下拉菜单',
            'icon'   => 'fas fa-list-ul',
            'fields' => array(
                array(
                    'id'      => 'opd_enabled',
                    'type'    => 'switcher',
                    'title'   => '启用增强下拉菜单',
                    'default' => true,
                    'help'    => '在首页顶部右侧显示下拉菜单，包含邮箱、网盘、地图等常用网址'
                ),
                array(
                    'id'         => 'opd_menus',
                    'type'       => 'group',
                    'title'      => '配置下拉分类及网址',
                    'dependency' => array( 'opd_enabled', '==', 'true' ),
                    'help'       => '添加下拉菜单分类，每个分类下可以添加多个网址链接',
                    'button_title' => '添加分类',
                    'fields'     => array(
                        array(
                            'id'          => 'title',
                            'type'        => 'text',
                            'title'       => '分类标题',
                            'placeholder' => '例如：邮箱、网盘、地图',
                            'help'        => '显示在下拉菜单按钮上的文字'
                        ),
                        array(
                            'id'      => 'icon_type',
                            'type'    => 'select',
                            'title'   => '图标类型',
                            'options' => array(
                                'theme'   => '使用主题图标',
                                'custom'  => '上传自定义图标',
                            ),
                            'default' => 'theme',
                            'help'    => '选择使用主题内置图标或上传自定义图标'
                        ),
                        array(
                            'id'         => 'icon',
                            'type'       => 'icon',
                            'title'      => '选择主题图标',
                            'dependency' => array( 'icon_type', '==', 'theme' ),
                            'button_title' => '添加图标',
                            'remove_title' => '删除图标',
                            'default'    => 'iconfont icon-version',
                            'help'       => '点击"添加图标"按钮从主题图标库中选择图标'
                        ),
                        array(
                            'id'         => 'icon_custom',
                            'type'       => 'upload',
                            'title'      => '上传自定义图标',
                            'dependency' => array( 'icon_type', '==', 'custom' ),
                            'library'    => array( 'image' ),
                            'preview'    => true,
                            'help'       => '上传自定义图标图片（建议尺寸：24x24px 或 32x32px，支持 PNG、SVG、JPG 格式）'
                        ),
                        array(
                            'id'            => 'items',
                            'type'          => 'group',
                            'title'         => '添加该分类下的网址',
                            'button_title'  => '添加网址',
                            'help'          => '为当前分类添加网址链接，可以添加多个',
                            'fields'        => array(
                                array(
                                    'id'          => 'title',
                                    'type'        => 'text',
                                    'title'       => '网址名称',
                                    'placeholder' => '例如：Gmail、百度网盘',
                                    'help'        => '显示在下拉菜单中的链接文字'
                                ),
                                array(
                                    'id'          => 'url',
                                    'type'        => 'text',
                                    'title'       => '链接地址',
                                    'placeholder' => 'https://example.com',
                                    'help'        => '完整的网址链接，必须以 http:// 或 https:// 开头',
                                ),
                                array(
                                    'id'      => 'target',
                                    'type'    => 'select',
                                    'title'   => '打开方式',
                                    'options' => array(
                                        '_blank' => '新标签页打开',
                                        '_self'  => '当前窗口打开',
                                    ),
                                    'default' => '_blank',
                                    'help'    => '选择链接的打开方式，默认为新标签页打开'
                                ),
                                array(
                                    'id'          => 'tooltip',
                                    'type'        => 'text',
                                    'title'       => '悬停说明',
                                    'placeholder' => '例如：访问Gmail邮箱服务',
                                    'help'        => '鼠标悬停在链接上时显示的提示文字（可选）'
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ) );
    }
}

OnenavProDropdown::get_instance();