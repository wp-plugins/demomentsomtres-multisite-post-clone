<?php
/*
  Plugin Name: DeMomentSomTres Multisite Post Clone
  Plugin URI: http://demomentsomtres.com/english/wordpress-plugins/demomentsomtres-multisite-post-clone/?utm_source=WPPlugins&utm_medium=Plugin&utm_campaign=MSPostClone
  Clone post to other network sites.
  Version: 1.0
  Author: DeMomentSomTres
  Author URI: http://www.DeMomentSomTres.com?utm_source=WPPlugins&utm_medium=Author&utm_campaign=MSPostClone
  License: GPLv2 or later
 */

/*
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

define('DMS3_MSPOSTCLONE_TEXT_DOMAIN', 'MultisitePostClone');

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
    exit;
}

$dms3MSPostClone = new DeMomentSomTresMSPostClone();

class DeMomentSomTresMSPostClone {

    const TEXT_DOMAIN = DMS3_MSPOSTCLONE_TEXT_DOMAIN;

    private $pluginURL;
    private $pluginPath;
    private $langDir;

    /**
     * @since 2.0
     */
    function DeMomentSomTresMSPostClone() {
        $this->pluginURL = plugin_dir_url(__FILE__);
        $this->pluginPath = plugin_dir_path(__FILE__);
        $this->langDir = dirname(plugin_basename(__FILE__)) . '/languages';

        add_action('plugins_loaded', array(&$this, 'plugin_init'));
    }

    /**
     * @since 2.0
     */
    function plugin_init() {
        load_plugin_textdomain(DMS3_MSPOSTCLONE_TEXT_DOMAIN, false, $this->langDir);
        add_action('add_meta_boxes', array($this, 'addCloner'));
        add_action('save_post', array($this, 'metaboxClonerSave'), 10, 3);
    }

    /**
     * @since 1.0
     * @return array contains a record for each blog with fields ID,name
     * @uses get_blog_list deprecated
     * @uses wp_get_sites() since version 1.4 and optimization to get only public sites 
     * Uses public=2 and public=1 in order to prevent problems
     */
    function getBlogs() {
        $info = array();
        $blogs = wp_get_sites(array(
            'public' => 2,
        ));
        $blogs1 = wp_get_sites(array(
            'public' => 1,
        ));
        $blogs = array_merge($blogs1, $blogs);
        foreach ($blogs as $blog):
            $details = get_blog_details($blog['blog_id']);
            $info[] = $details;
        endforeach;
        return $info;
    }

    /**
     * Activate metafields used to store relationships between elements
     * @since 2.0
     */
    function addCloner() {
        $clonableTypes = array('post');
        foreach ($clonableTypes as $tp):
            add_meta_box(
                    "DeMomentSomTresMSPostCloner", __('Multisite Clone', self::TEXT_DOMAIN), array($this, 'metaboxCloner'), $tp, 'side', 'high');
        endforeach;
    }

    function metaboxCloner($post) {
        global $blog_id;
        if ($post->post_status == 'publish'):
            $list = $this->getBlogs();
            $output .= '<p>' . __('Check the blogs where the content should be cloned and click ', self::TEXT_DOMAIN) . '<strong>'. __('Update',self::TEXT_DOMAIN).'</strong></p>';
            $output.='<table class="widefat"><tbody>';
            foreach ($list as $blog):
                if ($blog_id != $blog->blog_id):
                    $output .= '<tr><td>';
                    $output .='<label for="dms3msclone[' . $blog->blog_id . ']">' . $blog->blogname . ':</label>';
                    $output .= '</td><td>';
                    $output .='</td><td><input type="checkbox" name="dms3msclone[' . $blog->blog_id . ']"/>';
                    $output .= '</td></tr>';
                endif;
            endforeach;
            $output .= '</tbody></table>';
            else:
                $output .= '<p>' . __('Only published posts can be cloned to other sites.', self::TEXT_DOMAIN) . '</p>';
            endif;
//        $output.='<pre>'.print_r($list,true).'</pre>';
            echo $output;
    }

    /**
     * Saves relationship information
     * @param integer $post_id
     * @since 2.0
     */
    function metaboxClonerSave($post_id, $post, $update) {
        global $blog_id;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (defined('DOING_AJAX') && DOING_AJAX)
            return $postID;
        if ($post->post_status == 'trash' or $post->post_status == 'auto-draft')
            return $post_id;
        if (isset($_POST['dms3msclone'])):
            $dms3msclone = $_POST['dms3msclone'];
            foreach ($dms3msclone as $id => $dummy):
                switch_to_blog($id);
                remove_action('save_post', array($this, 'metaboxClonerSave'));
                $newPost = $post;
                unset($newPost->ID);
                $newPost->post_status = 'draft';
                $newpostid = wp_insert_post($newPost, TRUE);
                if (isset($_POST['yoast_seo_canonical']))
                    $canonical = $_POST['yoast_seo_canonical'];
                else
                    $canonical = get_permalink($postID);
                update_post_meta($newpostid, '_yoast_wpseo_canonical', $canonical);
                add_action('save_post', array($this, 'metaboxClonerSave'), 10, 3);
                restore_current_blog();
            endforeach;
//            echo '<pre>' . print_r($newpostid, true) . '</pre>';
//            exit;
        endif;
    }

}
?>