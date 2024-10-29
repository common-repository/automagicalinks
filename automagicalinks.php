<?php
/**
 * @package Automagicalinks
 * @version 0.1
 */
/*
Plugin Name: Automagicalinks
Plugin URI: https://github.com/saulbaizman/automagicalinks
Description: Automagically convert text to internal links on your website.
Author: Saul Baizman
Version: 0.1
Author URI: https://baizmandesign.com/
License: GPLv2
*/

register_activation_hook ( __FILE__, 'automagicalinks_set_default_options_array' );

function automagicalinks_set_default_options_array ( ) {

    $options = array (
        'autolinking' => '',
        'automagicality' => '',
        'link_characters' => '',
        'link_escape_character' => '',
        'allowed_post_types' => '',
        'allowed_link_names' => '',
        'excluded_elements' => '',
        'aliases' => '',
    ) ;

    update_option ( 'automagicalinks_options', $options );

}

add_action ( 'admin_menu', 'automagicalinks_admin_menu', 1 ) ;

function automagicalinks_admin_menu ( )
{
    add_options_page ( 'Automagicalinks Settings', 'Automagicalinks', 'manage_options', 'automagicalinks', 'automagicalinks_settings_page', 'dashicons-admin-links' );
}

add_action( 'admin_init', 'automagicalinks_settings' );

function automagicalinks_settings ( )
{
    register_setting( 'automagicalinks-plugin-settings-group', 'autolinking' );
    register_setting( 'automagicalinks-plugin-settings-group', 'automagicality' );
    register_setting( 'automagicalinks-plugin-settings-group', 'link_characters' );
    register_setting( 'automagicalinks-plugin-settings-group', 'link_escape_character' );
    register_setting( 'automagicalinks-plugin-settings-group', 'allowed_post_types' );
    register_setting( 'automagicalinks-plugin-settings-group', 'allowed_link_names' );
    register_setting( 'automagicalinks-plugin-settings-group', 'excluded_elements' );
    register_setting( 'automagicalinks-plugin-settings-group', 'aliases' );
}

add_action ( 'admin_post_update', 'save_automagicalinks_settings' ) ;

function save_automagicalinks_settings ( ) {
    // Check that user has proper security level
    if ( ! current_user_can ( 'manage_options' ) ) {
        wp_die ( 'Not allowed' ) ;
    }

    $automagicalinks_options = get_option ( 'automagicalinks_options' ) ;

    $updated_options = array ( ) ;

    foreach ( $automagicalinks_options as $option => $value ) {
        if ( isset ( $_POST[$option] ) ) {
            $updated_options[$option] = $_POST[$option] ;
        }
        else {
            // For the checkboxes.
            $updated_options[$option] = '' ;
        }
    }

    // Update the options.
    update_option ( 'automagicalinks_options', $updated_options );

    // Redirect with success=1 query string.
    wp_redirect (
        add_query_arg (
            array (
                'page' => 'automagicalinks',
                'message' => '1',
            ),
            admin_url ( 'options-general.php' )
        )
    );

}

function automagicalinks_settings_page ()
{

    $automagicalinks_options = get_option ( 'automagicalinks_options' ) ;

    ?>
    <div class="wrap">
        <h1>Automagicalinks settings</h1>
        <?php if ( isset( $_GET['message'] ) && $_GET['message'] == '1' ) : ?>
            <div id='message' class='updated fade'>
                <p><strong>Settings Saved</strong></p>
            </div>
        <?php endif ; ?>
        <form method="post" action="<?php echo get_admin_url() ; ?>admin-post.php">
            <?php settings_fields ( 'automagicalinks-plugin-settings-group' ) ; ?>
            <?php do_settings_sections ( 'automagicalinks-plugin-settings-group' ) ; ?>
            <table class="form-table">
            <tbody>
            <tr>
                <th scope="row" colspan="4">Post Types Whose Names Will Become Links</th>
            </tr>
            <?php

            $all_post_types = get_post_types ( )  ;

            $allowed_link_names = $automagicalinks_options['allowed_link_names'] ;

            $columns = 4 ;

            $column_counter = 0;

            $post_counter = 0 ;

            ksort ($all_post_types);

            foreach ( $all_post_types as $name => $value ) {

                if ( $column_counter%$columns == 0 ) {
                    printf( '<tr>' );
                }

                $replace['-'] = ' ' ;
                $replace['_'] = ' ' ;
                $replace['wp'] = 'WP' ;
                $name = ucwords ( strtr ( $name, $replace ) ) ;

                printf ( '<td><input type="checkbox" name="allowed_link_names[%1$s]" id="posts_%3$d" value="1"' . checked ( '1', isset ( $allowed_link_names[$value] ), false ) . '> <label for="posts_%3$d">%2$s</label></td>', $value, $name, $post_counter );

                $column_counter++ ;

                if ( $column_counter%$columns == 0 ) {
                    printf ( '</tr>' );
                    $column_counter = 0;
                }

                $post_counter++ ;
            }

            ?>
            </tbody>
            </table>

            <!--////////////////////////////////////////////////////////////-->

            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row" colspan="4">Post Types Whose Text Will Be Scanned For The Names Of The Post Types Identified Above</th>
                </tr>
                <?php

                $allowed_post_types = $automagicalinks_options['allowed_post_types'] ;

                $columns = 4 ;

                $column_counter = 0;

                $post_counter = 0 ;

                ksort ($all_post_types);

                foreach ( $all_post_types as $name => $value ) {

                    if ( $column_counter%$columns==0 ) {
                        printf( '<tr>' );
                    }

                    $replace['-'] = ' ' ;
                    $replace['_'] = ' ' ;
                    $replace['wp'] = 'WP' ;
                    $name = ucwords( strtr ( $name, $replace ) ) ;

                    printf ( '<td><input type="checkbox" name="allowed_post_types[%1$s]" id="posts_%3$d" value="1"' . checked ( '1', isset ( $allowed_post_types[$value] ), false ) . '> <label for="posts_%3$d">%2$s</label></td>', $value, $name, $post_counter );

                    $column_counter++ ;

                    if ( $column_counter%$columns==0 ) {
                        printf( '</tr>' );
                        $column_counter = 0;
                    }

                    $post_counter++ ;
                }

                ?>
                </tbody>
            </table>

            <!--////////////////////////////////////////////////////////////-->

            <table class="form-table">
                <tr valign="top">
                    <th scope="row" width="30%"><label for="autolinking">Enable Autolinking:</label></th>
                    <td width="70%"><input type="checkbox" name="autolinking" id="autolinking" value="1" <?php checked ( '1', $automagicalinks_options['autolinking'], true ); ?>/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><small>With autolinks, any text in the body of a page wrapped in link characters (below) and that matches a page name will be linked to that page.</small></th>
                    <td></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Link Characters:</th>
                    <td>
                        <select name="link_characters">
                            <?php

                            $link_character_pairs = array (
                                '[[ ]]',
                                '{{ }}',
                                '## ##',
                                '%% %%',
                                '|| ||',
                            ) ;

                            foreach ( $link_character_pairs as $pairs ) {
                                list ( $start, $end ) = explode ( ' ', $pairs ) ;
                                $selected = $pairs == $automagicalinks_options['link_characters'] ? ' selected' : '' ;
                                printf ( '<option value="%1$s %2$s"%4$s>%1$s%3$s%2$s</option>', $start, $end, 'text', $selected ) ;
                            }

                            ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Aliases:<br></th>
                    <td><textarea name="aliases" rows="8" cols="50" placeholder="William Smith=Will Smith,Willy Smith"><?php echo esc_attr ( $automagicalinks_options['aliases'] ) ; ?></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="automagicality">Enable Automagicality:</label></th>
                    <td><input type="checkbox" name="automagicality" id="automagicality"
                               value="1"<?php checked ( '1', $automagicalinks_options['automagicality'], true ); ?>/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><small style="color: red">Note: enabling Automagicality ignores the link characters. (Don't worry, they'll be removed.) Use for extreme awesome.</small></th>
                    <td></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Automagicalink Escape Characters:</th>
                    <td>
                        <select name="link_escape_character">
                        <?php

                        $escape_characters = array (
                            '!!',
                            '--',
                        ) ;

                        foreach ( $escape_characters as $characters ) {
                            $selected = $characters == $automagicalinks_options['link_escape_character'] ? ' selected' : '' ;
                            printf ( '<option value="%1$s"%2$s>%1$s</option>', $characters, $selected ) ;
                        }

                        ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><small>Each word in a phrase must be escaped to prevent automagical links from manifesting.</small></th>
                    <td></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Globally excluded phrases:<br></th>
                    <td><textarea name="excluded_elements" rows="8" cols="50" placeholder="Enter exclusion item per line."><?php echo esc_attr ( $automagicalinks_options['excluded_elements'] ); ?></textarea></td>
                </tr>
            </table>
            <?php

            ?>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }

function automagicalinks_filter ( $content ) {

    global $wpdb;

    $automagicalinks_options = get_option ( 'automagicalinks_options' ) ;

    $autolinking = $automagicalinks_options['autolinking'] ;
    $automagicality = $automagicalinks_options['automagicality'] ;
    $link_characters = $automagicalinks_options['link_characters'] ;

    list ( $link_start_characters, $link_end_characters ) = explode (' ', $link_characters ) ;

    $link_escape_character = $automagicalinks_options['link_escape_character'] ;
    $allowed_post_types = $automagicalinks_options['allowed_post_types'] ;
    $allowed_link_names = $automagicalinks_options['allowed_link_names'] ;
    $excluded_elements = $automagicalinks_options['excluded_elements'] ;
    $aliases = $automagicalinks_options['aliases'] ;

    if ( $autolinking ) {

        if ( is_singular ( ) && in_the_loop ( ) && is_main_query ( ) ) {

            // Check for whether we're looking at an allowed post type.
            $post_types = array_keys ( $allowed_post_types ) ;

            $this_post_type = get_post_type ( ) ;

            if ( ! in_array ( $this_post_type, $post_types ) ) {
                return $content ;
            }

            $replace_pairs = array ( ) ;
            $duplicates_pairs = array ( );

            $link_names = array_keys ( $allowed_link_names ) ;

            if ( ! $link_names ) {
                return $content ;
            }

            // Note our permalink structure below.
            // This may have to be customized for other sites!
            // In the wp_options table there is a record where option_name = "permalink_structure",
            // according to https://wordpress.stackexchange.com/questions/58625/where-is-permalink-info-stored-in-database

            $all_pages_sql = sprintf ("SELECT ID, post_title, post_name, post_type, concat_ws('/','%s', post_type, post_name,'') AS permalink FROM %s WHERE post_type IN ('%s') AND post_title != '%s' and post_status = '%s'",
                '//' . $_SERVER['HTTP_HOST'],
                $wpdb->posts,
                implode ( "','", $link_names ),
                'Auto Draft',
                'publish') ;

            $all_pages = $wpdb->get_results($all_pages_sql);

            if ( $all_pages ) {

                // Create aliases.
                if ( $aliases ) {

                    $aliases_substitutions = array ();

                    $aliases_array = explode( "\n", $aliases );

                    foreach ( $aliases_array as $alias ) {

                        // Is this a validly formatted line?
                        if ( strstr( $alias, '=' ) ) {

                            list ( $real_title, $synonyms ) = explode( '=', $alias );

                            // Does this alias have multiple synonyms?
                            if ( strstr( $synonyms, ',' ) ) {
                                $synonyms_array = explode( ',', $synonyms );
                            } else {
                                $synonyms_array[] = $synonyms;
                            }

                            foreach ( $synonyms_array as $synonym ) {
                                // The trim() call removes a newline character.
                                $aliases_substitutions[$real_title][] = trim ( $synonym ) ;
                            }

                            // Check that we're not replacing an alias with a page that exists.
                            foreach ( $all_pages as $page ) {
                                if ( $page->post_title == $real_title ) {
                                    unset ( $aliases_substitutions[$real_title] ) ;
                                }
                            }

                        }

                    }
                }

                foreach ( $all_pages as $page ) {

                    // Look for double brackets or not?
                    $search = $automagicality ? $page->post_title : $link_start_characters . $page->post_title . $link_end_characters;
                    $replace = sprintf( '<a href="%1$s">%2$s</a>', $page->permalink, $page->post_title );

                    // Fix "real" links that get nested after we automagically link them:
                    // <a href=""><a href="">Word</a></a>
                    $dupe_search = sprintf( '<a href="%1$s">',$page->permalink).sprintf( '<a href="%1$s">',$page->permalink).$page->post_title.'</a>'.'</a>';
                    $dupe_replace = $replace;

                    // Does the current page have any alias substitutions?
                    if ( isset ( $aliases_substitutions[$page->post_title]) ) {

                        foreach ( $aliases_substitutions as $real => $akas ) {

                            foreach ( $akas as $aka ) {

                                $aka_search = $automagicality ? $aka : $link_start_characters . $aka . $link_end_characters;
                                $aka_replace = sprintf( '<a href="%1$s">%2$s</a>', $page->permalink, $aka ) ;
                                $aka_dupe_search = sprintf( '<a href="%1$s">',$page->permalink).sprintf( '<a href="%1$s">',$page->permalink).$aka.'</a>'.'</a>';
                                $aka_dupe_replace = $aka_replace ;

                                $replace_pairs[ $aka_search ] = $aka_replace ;
                                $duplicates_pairs[ $aka_dupe_search ] = $aka_dupe_replace ;

                            }

                        }

                    }

                    $replace_pairs[ $search ] = $replace;
                    $duplicates_pairs[ $dupe_search ] = $dupe_replace;

                }

                /*
                 * Excluded the exceptions.
                 */
                $excluded_elements_array = explode("\n",$excluded_elements) ;

                if ($excluded_elements_array) {
                    foreach ( $excluded_elements_array as $excluded_element ) {
                        // Remove newline character.
                        $excluded_element = trim ( $excluded_element ) ;
                        if ( isset ( $replace_pairs[$excluded_element] ) ) {
                            unset ( $replace_pairs[$excluded_element] ) ;
                            unset ( $duplicates_pairs[$excluded_element]) ;
                        }
                    }
                }

                // The magic happens here.
                $content = strtr ( $content, $replace_pairs );
                $content = strtr ( $content, $duplicates_pairs );

                // Remove escape character.
                $content = str_replace ( $link_escape_character, '', $content );
            }
            else {
                return $content;
            }

        }

    }

    // Remove start characters, end characters, and escape characters in
    // case the user enables and later disables autolinking.
    // Requires the plugin to still be activated, of course!

    if ($link_start_characters) {
        $content = str_replace( $link_start_characters,'',$content) ;
    }
    if ($link_end_characters) {
        $content = str_replace( $link_end_characters,'',$content) ;
    }
    if ($link_escape_character) {
        $content = str_replace( $link_escape_character,'',$content) ;
    }

    return $content;

}

add_filter ( 'the_content', 'automagicalinks_filter' ) ;
