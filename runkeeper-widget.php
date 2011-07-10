<?php
/*
Plugin Name: Runkeeper Widget
Plugin URI: http://middleoftech.com/?page_id=288
Description: A simple widget that displays recent activities from Runkeeper. It's configurable through Appearance --> Widgets. Configuration options are; user name, activity display count.
Version: 1.0
Author: Ryan Baseman
Author URI: http://www.middleoftech.com
License: GPL
*/

include_once('simple_html_dom.php');

add_action("widgets_init", array('Runkeeper_Widget', 'register'));
register_activation_hook( __FILE__, array('Runkeeper_Widget', 'activate'));
register_deactivation_hook( __FILE__, array('Runkeeper_Widget', 'deactivate'));

class Runkeeper_Widget {
  function activate(){
    if ( ! get_option('runkeeper_widget_username')){
      add_option('runkeeper_widget_username' , '');
      add_option('runkeeper_widget_activity_count', '10');
    } else {
      update_option('runkeeper_widget_username' , '');
      update_option('runkeeper_widget_activity_count', '10');
    }
  }
  function deactivate(){
    delete_option('runkeeper_widget_username');
    delete_option('runkeeper_widget_activity_count');
  }
  function control(){
    ?>
    <p>
      <label>Runkeeper Username<input name="runkeeper_user" type="text" value="<?php echo get_option('runkeeper_widget_username'); ?>"/>
      </label>
    </p>
    <p>
      <label>Count of Activities to List<input name="runkeeper_activity_count" type="text" value="<?php echo get_option('runkeeper_widget_activity_count'); ?>"/>
      </label>
    </p>
    <?php
       if (isset($_POST['runkeeper_user'])){
         update_option('runkeeper_widget_username', $_POST['runkeeper_user']);
       } 
       if (isset($_POST['runkeeper_activity_count'])){
         update_option('runkeeper_widget_activity_count', $_POST['runkeeper_activity_count']);
       }     
  }
  function widget($args){
    echo $args['before_widget'];
    echo $args['before_title'] . 'Recent Runkeeper Activity' . $args['after_title'];
    echo '<p><i>';

    if (get_option('runkeeper_widget_username') == null) {
      echo 'Runkeeper widget not configured.';
    }
    else
    {
      $url = 'http://runkeeper.com/user/';
      $url .= get_option('runkeeper_widget_username');
      $url .= '/activity/';

      $runkeeperHtml = file_get_html($url);

      $firstActivityHtml = $runkeeperHtml->find('div[class=activityMonth menuItem selected]', 0);
      $firstActivityString = $firstActivityHtml->find('div[class=mainText]', 0)->innertext;
      $firstActivityString .= ' ';
      $firstActivityString .= $firstActivityHtml->find('div[class=distance]', 0)->innertext;
      $firstActivityString .= ' ';
      $firstActivityString .= $firstActivityHtml->find('div[class=distanceUnit]', 0)->innertext;
      echo $firstActivityString;

      $i = 1;
      foreach($runkeeperHtml->find('div[class=activityMonth menuItem ]') as $activity)
      {
        $activityString = '<br>';
        $activityString .= $activity->find('div[class=mainText]', 0)->innertext;
        $activityString .= ' ';
        $activityString .= $activity->find('div[class=distance]', 0)->innertext;
        $activityString .= ' ';
        $activityString .= $activity->find('div[class=distanceUnit]', 0)->innertext;
        echo $activityString;

        $activityString = '';
        if ($i > get_option('runkeeper_widget_activity_count'))
        {
           break;
        }
        $i++;
      }

      echo '</i>';
    }

    echo $args['after_widget'];
  }
  function register(){
    register_sidebar_widget('Runkeeper', array('Runkeeper_Widget', 'widget'));
    register_widget_control('Runkeeper', array('Runkeeper_Widget', 'control'));
  }
}
?>
