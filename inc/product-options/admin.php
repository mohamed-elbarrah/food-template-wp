<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Render UI inside the General > Pricing panel (below price fields) for better UX
add_action( 'woocommerce_product_options_pricing', function() {
    global $post;
    $saved = bcpo_get_saved_options( $post->ID );
    echo '<div id="bcpo_product_options" class="options_group">';
    // Main section title and top description (Arabic)
    echo '<h3 class="bcpo-main-title">' . esc_html__( 'تخصيصات المنتج (خيارات إضافية)', 'blocksy-child' ) . '</h3>';
    echo '<p class="bcpo-top-desc">' . esc_html__( 'من هنا يمكنك إنشاء أقسام التخصيص الخاصة بالمنتج مثل الحجم، مستوى الحرارة، الإضافات، أو أي خيارات أخرى يريد العميل اختيارها قبل الطلب.', 'blocksy-child' ) . '</p>';
    echo '<p class="bcpo-top-desc">' . esc_html__( 'كل قسم يمثل مجموعة خيارات واحدة (مثال: اختر الحجم). يمكنك إضافة سعر إضافي لكل خيار إذا لزم الأمر.', 'blocksy-child' ) . '</p>';

    // Help box
    echo '<div class="bcpo-helpbox">';
    echo '<strong>' . esc_html__( 'كيف تعمل خيارات المنتج؟', 'blocksy-child' ) . '</strong>';
    echo '<ol class="bcpo-help-steps">';
    echo '<li>' . esc_html__( 'أضف قسم تخصيص جديد', 'blocksy-child' ) . '</li>';
    echo '<li>' . esc_html__( 'اكتب عنوان القسم (مثال: اختر الحجم)', 'blocksy-child' ) . '</li>';
    echo '<li>' . esc_html__( 'اختر طريقة الاختيار', 'blocksy-child' ) . '</li>';
    echo '<li>' . esc_html__( 'أضف الخيارات وحدد السعر إن وجد', 'blocksy-child' ) . '</li>';
    echo '<li>' . esc_html__( 'احفظ المنتج', 'blocksy-child' ) . '</li>';
    echo '</ol>';
    echo '<p class="bcpo-help-note">' . esc_html__( 'سيظهر هذا القسم تلقائياً للعميل في صفحة المنتج أو نافذة الطلب السريع.', 'blocksy-child' ) . '</p>';
    echo '</div>';

    wp_nonce_field( 'bcpo_save_options', 'bcpo_nonce' );
    echo '<div class="bcpo-groups-wrap">';
    echo '<div id="bcpo-groups" data-saved="' . esc_attr( wp_json_encode( $saved ) ) . '"></div>';
    echo '<p class="bcpo-actions"><button type="button" class="button" id="bcpo-add-group">' . esc_html__( 'إضافة مجموعة تخصيص', 'blocksy-child' ) . '</button></p>';
    echo '<input type="hidden" id="bcpo_payload" name="bcpo_payload" value="' . esc_attr( wp_json_encode( $saved ) ) . '">';
    echo '</div>';
    echo '</div>';
} );
