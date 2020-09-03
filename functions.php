<?php
  /* 直接アクセスを禁止
  ----------------------------------------------- */
  if (!defined('ABSPATH')) {
      exit;
  }

  /* 「子テーマ」用css, jsのロード
  ----------------------------------------------- */
  function theme_enqueue_styles()
  {
      wp_dequeue_style('sdm-styles');
      wp_dequeue_style('toc-screen');

      wp_enqueue_style('parent-style', get_template_directory_uri().'/style.min.css', array('normalize', 'fontawesome'));
      wp_enqueue_style('custom-style', get_stylesheet_directory_uri().'/style.min.css', array('parent-style'));
  }
  add_action('wp_enqueue_scripts', 'theme_enqueue_styles');

  function theme_enqueue_script()
  {
      wp_register_script('childfunctions', get_stylesheet_directory_uri().'/js/functions.min.js', array());
      wp_enqueue_script('childfunctions');
  }
  add_action('wp_print_scripts', 'theme_enqueue_script');

  /* プラグインのcssはフッターで出力させる
  ----------------------------------------------- */
  function enqueue_css_footer()
  {
      wp_enqueue_style('sdm-styles');
      wp_enqueue_style('toc-screen');
  }
  add_action('wp_footer', 'enqueue_css_footer');

  /* スクリプトの読み込みタイミング調整
  ----------------------------------------------- */
  if (!is_admin()) {
      if (!function_exists('replace_script_tag')) {
          function replace_script_tag($tag)
          {
              if (strpos($tag, 'jquery.min.js') || strpos($tag, 'layzr.js') || strpos($tag, 'syntaxhighlighter3')) {
                  return str_replace("type='text/javascript' ", '', $tag);
              } else {
                  return str_replace("type='text/javascript'", 'async', $tag);
              }
          }
          add_filter('script_loader_tag', 'replace_script_tag');
      }
  }

  /* 「子テーマ」用カスタムヘッダ―再定義
  ----------------------------------------------- */
  remove_theme_support('custom-header');
  $customHeader = array(
    'default-image' => '',
    'random-default' => false,
    'width' => 1000,
    'height' => 250,
    'flex-height' => true,
    'flex-width' => false,
    'header-text' => true,
    'default-text-color' => '',
    'uploads' => true,
    'wp-head-callback' => '',
    'admin-head-callback' => '',
    'admin-preview-callback' => '',
    'video' => false,
    'video-active-callback' => 'is_front_page',
  );
  add_theme_support('custom-header', $customHeader);

    /* ウィジェットの登録
  ----------------------------------------------- */
  if (!function_exists('childtheme_register_widget')) {
      function childtheme_register_widget()
      {
      }
      add_action('widgets_init', 'childtheme_register_widget');
  }

  /* 自動保存を無効
  ----------------------------------------------- */
  function autosave_off()
  {
      wp_deregister_script('autosave');
  }
  add_action('wp_print_scripts', 'autosave_off');

  /* コメントフォームのカスタマイズ
  ----------------------------------------------- */
  function custom_comment_form_default_fields($arg)
  {
      unset($arg['url']);
      $arg['author'] = '<p class="comment-form-author"><input id="author" name="author" type="text" placeholder="名前" value="' . esc_attr($commenter['comment_author']).'" ' . $aria_req . ' /></p>';
      $arg['email'] = '<p class="comment-form-email"><input id="email" name="email" type="text" placeholder="メールアドレス" value="'.esc_attr($commenter['comment_author_email']).'" ' . $aria_req . ' /></p>';
      $arg['cookies'] = '<p class="comment-form-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"'.$consent.' /><label for="wp-comment-cookies-consent">名前、メールアドレスを保存する。</label></p>';
      return $arg;
  }
  add_filter('comment_form_default_fields', 'custom_comment_form_default_fields');

  function custom_comment_form_fields($arg)
  {
      $arg['comment'] = '<p class="comment-form-comment font-awesome"><textarea id="comment" name="comment" aria-required="true" placeholder="コメントを入力してください……"></textarea></p>';
      return $arg;
  }
  add_filter('comment_form_fields', 'custom_comment_form_fields');

  function custom_comment_form($arg)
  {
      $arg['title_reply'] = 'コメント';
      $arg['comment_notes_before'] = '';
      $arg['comment_notes_after'] = '';
      $arg['label_submit'] = '送信';
      return $arg;
  }
  add_filter('comment_form_defaults', 'custom_comment_form');

  function custom_preprocess_comment($arg)
  {
      if (empty(trim($arg['comment_author']))) {
          wp_die('<strong>エラー</strong>: 名前を入力してください。');
      }
      return $arg;
  }
  add_filter('preprocess_comment', 'custom_preprocess_comment', 1);

  /* 個別のcssを使えるようにする
  ----------------------------------------------- */
  add_action('admin_menu', 'custom_css_hooks');
  add_action('save_post', 'save_custom_css');
  add_action('wp_head', 'insert_custom_css');
  function custom_css_hooks()
  {
      add_meta_box('custom_css', '個別CSS', 'custom_css_input', 'post', 'normal', 'high');
      add_meta_box('custom_css', '個別CSS', 'custom_css_input', 'page', 'normal', 'high');
  }
  function custom_css_input()
  {
      global $post;
      echo '<input type="hidden" name="custom_css_noncename" id="custom_css_noncename" value="'.wp_create_nonce('custom-css').'" />';
      echo '<textarea name="custom_css" id="custom_css" rows="5" cols="30" style="width:100%;">'.get_post_meta($post->ID, '_custom_css', true).'</textarea>';
  }
  function save_custom_css($post_id)
  {
      if (!wp_verify_nonce($_POST['custom_css_noncename'], 'custom-css')) {
          return $post_id;
      }
      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
          return $post_id;
      }
      $custom_css = $_POST['custom_css'];
      update_post_meta($post_id, '_custom_css', $custom_css);
  }
  function insert_custom_css()
  {
      if (is_page() || is_single()) {
          if (have_posts()) : while (have_posts()) : the_post();
          if (get_post_meta(get_the_ID(), '_custom_css', true) !='') {
              echo "<style type=\"text/css\" media=\"all\">\n".get_post_meta(get_the_ID(), '_custom_css', true)."\n</style>\n";
          }
          endwhile;
          endif;
          rewind_posts();
      }
  }

  /* 個別のJavaScriptを使えるようにする
  ----------------------------------------------- */
  add_action('admin_menu', 'custom_js_hooks');
  add_action('save_post', 'save_custom_js');
  add_action('wp_head', 'insert_custom_js');
  function custom_js_hooks()
  {
      add_meta_box('custom_js', '個別JavaScript', 'custom_js_input', 'post', 'normal', 'high');
      add_meta_box('custom_js', '個別JavaScript', 'custom_js_input', 'page', 'normal', 'high');
  }
  function custom_js_input()
  {
      global $post;
      echo '<input type="hidden" name="custom_js_noncename" id="custom_js_noncename" value="'.wp_create_nonce('custom-js').'" />';
      echo '<textarea name="custom_js" id="custom_js" rows="5" cols="30" style="width:100%;">'.get_post_meta($post->ID, '_custom_js', true).'</textarea>';
  }
  function save_custom_js($post_id)
  {
      if (!wp_verify_nonce($_POST['custom_js_noncename'], 'custom-js')) {
          return $post_id;
      }
      if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
          return $post_id;
      }
      $custom_js = $_POST['custom_js'];
      update_post_meta($post_id, '_custom_js', $custom_js);
  }
  function insert_custom_js()
  {
      if (is_page() || is_single()) {
          if (have_posts()) : while (have_posts()) : the_post();
          if (get_post_meta(get_the_ID(), '_custom_js', true) !='') {
              echo "<script type=\"text/javascript\">\n".get_post_meta(get_the_ID(), '_custom_js', true)."\n</script>\n";
          }
          endwhile;
          endif;
          rewind_posts();
      }
  }
