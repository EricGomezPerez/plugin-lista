<?php
/**
 * Utilidad para limpiar la caché de transients de Entrapolis en WordPress.
 *
 * Este archivo proporciona funciones para eliminar la caché generada por el plugin Entrapolis,
 * permitiendo borrar los transients relacionados y limpiar la caché de objetos si está disponible.
 *
 * @package Entrapolis
 */


// Evita el acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}


/**
 * Elimina todos los transients de WordPress relacionados con Entrapolis.
 *
 * Busca y elimina las opciones en la base de datos que comienzan con '_transient_entrapolis_' o
 * '_transient_timeout_entrapolis_'. Además, limpia la caché de objetos si está disponible.
 *
 * @return bool Verdadero si la operación se realiza correctamente.
 */
function entrapolis_clear_cache()
{
    global $wpdb;

    // Elimina todos los transients que empiezan por 'entrapolis_'
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE %s 
            OR option_name LIKE %s",
            $wpdb->esc_like('_transient_entrapolis_') . '%',
            $wpdb->esc_like('_transient_timeout_entrapolis_') . '%'
        )
    );

    // Limpia la caché de objetos si existe
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }

    return true;
}


/**
 * Añade el botón para limpiar la caché en la página de ajustes del administrador.
 *
 * Verifica los permisos del usuario y gestiona la acción de limpieza de caché cuando se envía el formulario.
 * Si la caché se limpia correctamente, muestra un mensaje de confirmación en la interfaz de administración.
 *
 * @return void
 */
function entrapolis_add_cache_clear_button()
{
    // Solo administradores pueden limpiar la caché
    if (!current_user_can('manage_options')) {
        return;
    }

    // Gestiona la acción de limpiar caché
    if (isset($_POST['entrapolis_clear_cache']) && check_admin_referer('entrapolis_clear_cache_action')) {
        entrapolis_clear_cache();
        add_settings_error('entrapolis_messages', 'cache_cleared', 'Caché limpiada correctamente', 'updated');
    }
}

// Registra la función para añadir el botón de limpieza de caché en el hook de administración
add_action('admin_init', 'entrapolis_add_cache_clear_button');
