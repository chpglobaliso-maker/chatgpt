<?php
/**
 * Plugin Name: CHP Site
 * Description: Contenido corporativo, catálogos y solicitudes privadas para CHP GLOBAL ISO SpA.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
add_action('init', function () {
    foreach (['chp_norma' => ['Normas', 'norma'], 'chp_curso' => ['Cursos', 'curso'], 'chp_caso' => ['Casos', 'caso']] as $key => $data) {
        register_post_type($key, ['label' => $data[0], 'public' => true, 'show_in_rest' => true, 'has_archive' => false, 'rewrite' => ['slug' => $data[1]], 'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions']]);
    }
    register_taxonomy('chp_area', ['chp_norma', 'chp_curso'], ['label' => 'Áreas de gestión', 'public' => true, 'show_in_rest' => true]);
    register_post_type('chp_solicitud', ['label' => 'Solicitudes CHP', 'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'supports' => ['title', 'editor'], 'capability_type' => 'post', 'capabilities' => ['edit_posts' => 'manage_options', 'edit_others_posts' => 'manage_options', 'publish_posts' => 'manage_options', 'read_private_posts' => 'manage_options', 'delete_posts' => 'manage_options', 'edit_post' => 'manage_options', 'read_post' => 'manage_options', 'delete_post' => 'manage_options', 'create_posts' => 'do_not_allow'], 'map_meta_cap' => false]);
});
add_action('admin_menu', function () { add_management_page('Preparar sitio CHP', 'Preparar sitio CHP', 'manage_options', 'chp-setup', 'chp_setup_screen'); });
function chp_setup_screen() {
    if (!current_user_can('manage_options')) { return; }
    echo '<div class="wrap"><h1>Preparar sitio CHP</h1><p>Importa el contenido como borradores, sin reemplazar contenido existente. Publica después de verificar respaldo y vista previa.</p>';
    if (isset($_GET['done'])) { echo '<div class="notice notice-success"><p>Operación completada.</p></div>'; }
    echo '<p>Tema activo: ' . esc_html(wp_get_theme()->get('Name')) . '</p><p>Contenido importado: ' . (get_option('chp_seeded') ? 'Sí' : 'No') . '</p>';
    foreach (['seed' => 'Crear borradores CHP', 'publish' => 'Publicar contenido CHP y configurar Inicio'] as $op => $label) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="chp_setup"><input type="hidden" name="operation" value="' . esc_attr($op) . '">';
        wp_nonce_field('chp_setup'); submit_button($label); echo '</form>';
    }
    echo '<p>El envío público se habilita tras marcar el consentimiento. Las consultas se guardan como privadas y se intenta notificar al correo configurado en el código mediante wp_mail. Revisar entrega y SMTP en las pruebas de puesta en marcha.</p></div>';
}
add_action('admin_post_chp_setup', function () {
    if (!current_user_can('manage_options')) { wp_die('Acceso denegado.', '', ['response' => 403]); }
    check_admin_referer('chp_setup');
    $op = sanitize_key($_POST['operation'] ?? '');
    if ($op === 'seed') {
        $items = json_decode(file_get_contents(__DIR__ . '/seed.json'), true);
        if (!is_array($items)) { wp_die('No se pudo leer el contenido.'); }
        foreach ($items as $item) {
            if (get_page_by_path($item['slug'], OBJECT, $item['type'])) { continue; }
            $content = str_replace('{{theme}}', esc_url(get_theme_root_uri() . '/chp-global-iso'), $item['content']);
            $id = wp_insert_post(['post_type' => $item['type'], 'post_name' => $item['slug'], 'post_title' => $item['title'], 'post_content' => $content, 'post_excerpt' => $item['excerpt'], 'post_status' => 'draft', 'comment_status' => 'closed', 'ping_status' => 'closed'], true);
            if (is_wp_error($id)) { wp_die(esc_html($id->get_error_message())); }
            update_post_meta($id, '_chp_owned', '1');
            if (!empty($item['area'])) { wp_set_object_terms($id, $item['area'], 'chp_area'); }
        }
        update_option('chp_seeded', '1', false);
    } elseif ($op === 'publish') {
        if (get_stylesheet() !== 'chp-global-iso') { wp_die('Activa primero el tema CHP Global ISO.'); }
        $posts = get_posts(['post_type' => ['page', 'chp_norma', 'chp_curso'], 'post_status' => 'draft', 'numberposts' => -1, 'meta_key' => '_chp_owned', 'meta_value' => '1']);
        foreach ($posts as $post) { wp_update_post(['ID' => $post->ID, 'post_status' => 'publish']); }
        $front = get_page_by_path('inicio');
        if ($front && get_post_meta($front->ID, '_chp_owned', true)) {
            if (!get_option('chp_previous_front')) { update_option('chp_previous_front', ['show_on_front' => get_option('show_on_front'), 'page_on_front' => get_option('page_on_front'), 'timezone_string' => get_option('timezone_string')], false); }
            update_option('show_on_front', 'page'); update_option('page_on_front', $front->ID); update_option('timezone_string', 'America/Santiago');
        }
        $privacy = get_page_by_path('privacidad');
        if ($privacy) { update_option('wp_page_for_privacy_policy', $privacy->ID); }
        flush_rewrite_rules();
    }
    wp_safe_redirect(admin_url('tools.php?page=chp-setup&done=1')); exit;
});
add_shortcode('chp_catalog', function ($attributes) {
    $a = shortcode_atts(['type' => 'chp_norma'], $attributes);
    $type = in_array($a['type'], ['chp_norma', 'chp_curso', 'chp_caso'], true) ? $a['type'] : 'chp_norma';
    $query = new WP_Query(['post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'ID', 'order' => 'ASC']);
    if (!$query->have_posts()) { return '<p>El catálogo se está preparando. Puedes registrar tu interés en el formulario.</p>'; }
    ob_start();
    if ($type === 'chp_curso') { echo '<div class="chp-filter"><label for="chp-course-filter">Filtrar por nivel <select id="chp-course-filter"><option value="">Todos los niveles</option><option>Inicial</option><option>Aplicación</option><option>Auditoría</option></select></label><p id="chp-course-count" role="status" aria-live="polite"></p></div>'; }
    echo '<div class="chp-grid" data-chp-catalog>';
    while ($query->have_posts()) { $query->the_post(); $terms = wp_get_post_terms(get_the_ID(), 'chp_area', ['fields' => 'names']);
        echo '<article class="chp-card" data-level="' . esc_attr(implode(' ', is_wp_error($terms) ? [] : $terms)) . '"><span class="chp-index">' . esc_html(implode(' · ', is_wp_error($terms) ? [] : $terms)) . '</span><h3>' . esc_html(get_the_title()) . '</h3><p>' . esc_html(get_the_excerpt()) . '</p><a href="' . esc_url(get_permalink()) . '">Ver ficha <span aria-hidden="true">↗</span></a></article>';
    }
    echo '</div>'; wp_reset_postdata(); return ob_get_clean();
});
add_shortcode('chp_form', function () {
    $state = sanitize_key($_GET['solicitud'] ?? '');
    ob_start();
    if ($state) { echo '<div class="chp-container"><div class="chp-alert" role="status">' . esc_html($state === 'error' ? 'No pudimos registrar la solicitud. Revisa los campos y vuelve a intentarlo. Si el problema continúa, inténtalo más tarde.' : 'Tu solicitud quedó registrada para revisión. Este mensaje no confirma entrega de correo ni una contratación.') . '</div></div>'; }
    ?><form class="chp-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="chp_lead"><?php wp_nonce_field('chp_lead', 'chp_nonce'); ?>
    <div class="wide"><p>Los campos con * son obligatorios. El registro de interés no tiene costo ni reserva cupos.</p></div>
    <?php foreach (['nombre' => ['Nombre *', 'text', true], 'email' => ['Correo electrónico *', 'email', true], 'empresa' => ['Empresa u organización', 'text', false], 'ubicacion' => ['Comuna y región *', 'text', true]] as $key => $field) { ?>
    <label><?php echo esc_html($field[0]); ?><input type="<?php echo esc_attr($field[1]); ?>" name="<?php echo esc_attr($key); ?>" maxlength="160" <?php echo $field[2] ? 'required' : ''; ?> autocomplete="<?php echo esc_attr(['nombre' => 'name', 'email' => 'email', 'empresa' => 'organization', 'ubicacion' => 'address-level2'][$key]); ?>"></label>
    <?php } ?>
    <?php foreach (chp_form_options() as $key => $data) { ?>
    <label><?php echo esc_html($data[0]); ?><select name="<?php echo esc_attr($key); ?>" required><option value="">Selecciona una opción</option><?php foreach ($data[1] as $option) { echo '<option>' . esc_html($option) . '</option>'; } ?></select></label>
    <?php } ?>
    <label class="wide">Cuéntanos tu necesidad *<textarea name="mensaje" rows="5" maxlength="3000" required></textarea></label>
    <label class="chp-honey" aria-hidden="true">Deja este campo vacío<input name="website" tabindex="-1" autocomplete="off"></label>
    <label class="wide check"><input type="checkbox" name="consentimiento" value="1" required><span>Autorizo a CHP GLOBAL ISO SpA a usar estos datos para responder y gestionar esta solicitud, según la <a href="<?php echo esc_url(home_url('/privacidad/')); ?>">política de privacidad</a>. No autoriza publicidad.</span></label>
    <div class="wide"><button class="chp-button" type="submit">Enviar solicitud <span aria-hidden="true">↗</span></button></div>
    </form><?php return ob_get_clean();
});
function chp_form_options() {
    return ['cliente' => ['Tipo de cliente *', ['Empresa', 'Persona']], 'industria' => ['Industria *', ['Minería', 'Energía', 'Construcción', 'Transporte y logística', 'Alimentos', 'Servicios', 'Pyme', 'Otra', 'No aplica']], 'servicio' => ['Servicio de interés *', ['Implementación ISO', 'Auditorías', 'Preparación para certificación', 'Acompañamiento', 'ERP', 'Gestión documental', 'Capacitación', 'Consulta general']], 'norma' => ['Norma de interés *', ['ISO 9001', 'ISO 14001', 'ISO 45001', 'ISO 22000', 'ISO/IEC 27001', 'ISO 31000', 'ISO 19011', 'ISO 26000', 'Varias normas', 'Por definir', 'No aplica']], 'trabajadores' => ['Número aproximado de trabajadores *', ['1–10', '11–50', '51–200', 'Más de 200', 'No aplica']], 'plazo' => ['Plazo esperado *', ['Este mes', '1–3 meses', 'Más de 3 meses', 'Por definir']]];
}
add_action('admin_post_nopriv_chp_lead', 'chp_handle_lead');
add_action('admin_post_chp_lead', 'chp_handle_lead');
function chp_handle_lead() {
    $error = home_url('/cotizar/?solicitud=error');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chp_nonce'] ?? '')), 'chp_lead')) { wp_safe_redirect($error); exit; }
    $data = [];
    foreach (['nombre', 'email', 'empresa', 'ubicacion', 'mensaje'] as $key) {
        $value = $_POST[$key] ?? '';
        if (!is_string($value) || strlen($value) > ($key === 'mensaje' ? 12000 : 640)) { wp_safe_redirect($error); exit; }
        $data[$key] = $key === 'mensaje' ? sanitize_textarea_field(wp_unslash($value)) : sanitize_text_field(wp_unslash($value));
    }
    if (!$data['nombre'] || !$data['ubicacion'] || !$data['mensaje'] || !is_email($data['email']) || ($_POST['consentimiento'] ?? '') !== '1' || !empty($_POST['website'])) { wp_safe_redirect($error); exit; }
    foreach (chp_form_options() as $key => $definition) {
        $value = $_POST[$key] ?? '';
        if (!is_string($value) || !in_array(wp_unslash($value), $definition[1], true)) { wp_safe_redirect($error); exit; }
        $data[$key] = sanitize_text_field(wp_unslash($value));
    }
    // Hashes are short-lived rate-limit identifiers; the raw IP is not stored.
    $limit = 'chp_rate_' . hash_hmac('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . strtolower($data['email']), wp_salt('nonce'));
    if (get_transient($limit)) { wp_safe_redirect($error); exit; }
    $text = '';
    foreach ($data as $key => $value) { $text .= $key . ': ' . $value . "\n\n"; }
    $text .= 'Consentimiento: responder y gestionar la solicitud. Política v1.0 · ' . current_time('mysql') . "\n";
    $id = wp_insert_post(['post_type' => 'chp_solicitud', 'post_status' => 'private', 'post_title' => 'Solicitud ' . current_time('Y-m-d H:i:s'), 'post_content' => wp_slash($text)], true);
    if (is_wp_error($id) || !$id) { wp_safe_redirect($error); exit; }
    set_transient($limit, '1', 120);
    // Notification contains no submitted personal data; administrators read it in WordPress.
    $sent = wp_mail('contacto@chpglobaliso.cl', 'Nueva solicitud CHP #' . $id, 'Hay una solicitud registrada. Revísala iniciando sesión: ' . admin_url('post.php?post=' . $id . '&action=edit'));
    update_post_meta($id, '_chp_mail_accepted', $sent ? '1' : '0');
    wp_safe_redirect(home_url('/gracias/?solicitud=registrada')); exit;
}
add_action('wp_head', function () {
    if (!is_singular()) { return; }
    // Avoid duplicate SEO output if a dedicated SEO plugin is activated later.
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')) { return; }
    $description = wp_strip_all_tags(get_the_excerpt());
    echo '<meta name="description" content="' . esc_attr($description) . '">';
    echo '<meta property="og:title" content="' . esc_attr(wp_get_document_title()) . '"><meta property="og:description" content="' . esc_attr($description) . '"><meta property="og:type" content="website"><meta property="og:url" content="' . esc_url(get_permalink()) . '"><meta property="og:locale" content="es_CL">';
    echo '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'CHP GLOBAL ISO SpA', 'url' => home_url('/')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
});
add_filter('wp_robots', function ($robots) { if (is_page('gracias')) { $robots['noindex'] = true; } return $robots; });
add_action('wp_enqueue_scripts', function () { wp_enqueue_script('chp-catalog', plugins_url('catalog.js', __FILE__), [], '1.0.0', true); });
