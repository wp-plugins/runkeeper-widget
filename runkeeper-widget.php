<?php
/*
Plugin Name: Runkeeper Widget
Plugin URI: http://middleoftech.com/?page_id=288
Description: A simple widget that displays recent activities from Runkeeper. It's configurable through Appearance --> Widgets. Configuration options are; user name, activity display count.
Version: 3.0
Author: Ryan Baseman/Jason Stanard
Author URI: http://www.middleoftech.com
License: GPL
*/

include_once('simple_html_dom.php');

add_action("widgets_init", array('Runkeeper_Widget', 'register'));
register_activation_hook( __FILE__, array('Runkeeper_Widget', 'activate'));
register_deactivation_hook( __FILE__, array('Runkeeper_Widget', 'deactivate'));

class Runkeeper_Widget {
  /* ---- Activate Function ---- */
  /* ---- Function called when Runkeeper widget is activated in the wordpress menu. ---- */
  function activate(){
    if ( ! get_option('runkeeper_widget_username')){
      add_option('runkeeper_widget_username' , '');
      add_option('runkeeper_widget_activity_count', '10');
    } else {
      update_option('runkeeper_widget_username' , '');
      update_option('runkeeper_widget_activity_count', '10');
    }
  }

  /* ---- Deactivate Function ---- */
  /* ---- Function called when Runkeeper widget is deactivated in the wordpress menu. ---- */
  function deactivate(){
    delete_option('runkeeper_widget_username');
    delete_option('runkeeper_widget_activity_count');
  }

  /* ---- Control Function ---- */
  /* ---- Main functionality of runkeeper widget ---- */
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
      $url .= '/activitylist/';

      $runkeeperHtml = file_get_html($url);

      /* ----  Get date and activity count ---- 
          Get the month and activities for that month inside accordion-m class.
          Build two arrays, one to hold month and one to hold number of activities. 
          We don't need to parse the entire page if we only want the first few 
          activities so jump out of the loop when the sum of activities is larger 
          than runkeeper_widget_activity_count.
      --------------------*/ 
      $i = 0;
      foreach($runkeeperHtml->find('div[class=accordion-m]') as $accordion)
      {
         $month_arr[$i] = $accordion->find('div[class=mainText]', 0)->innertext;
         preg_match('/Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec/', $month_arr[$i], $matchmonth);
         preg_match('/\d{4}/', $month_arr[$i], $matchyear);         
         $month_arr[$i] = "$matchmonth[0] $matchyear[0]";
         $item_arr[$i] = $accordion->find('div[class=bubble-text]', 0)->innertext;

         if (array_sum($item_arr) > get_option('runkeeper_widget_activity_count'))
         {
           break;
         }
       $i++;
       }
      
      $firstActivityHtml = $runkeeperHtml->find('div[class=activityMonth menuItem selected]', 0);

      /* ----  Get activity link ---- */
      $beginstr = "link=";
      $endstr = ">";
      $beginstrpos = (strpos($firstActivityHtml,$beginstr) + 6);
      $endstrpos = (strpos($firstActivityHtml,$endstr) - 1);
      $endstrpos = $endstrpos - $beginstrpos;
      $link = substr($firstActivityHtml,$beginstrpos,$endstrpos);

      /* ----  Get day from image src link ---- */ 
      preg_match('/\-\d{1,2}/', $firstActivityHtml->find('div[class=day]', 0)->innertext, $matchday);
      $day=$matchday[0];
      preg_match('/\d{1,2}/', $day, $matchday);
      $date = "$matchday[0] $month_arr[0] ";

      /* ---- FirstActivityString is changed a little to accomodate link and date ---- */
      $firstActivityString = $firstActivityHtml->find('div[class=mainText]', 0)->innertext;
      $firstActivityString .= ' ';
      $firstActivityString .= $firstActivityHtml->find('div[class=distance]', 0)->innertext;
      $firstActivityString .= ' ';
      $firstActivityString .= $firstActivityHtml->find('div[class=distanceUnit]', 0)->innertext;
      echo "<a target=_blank href=http://www.runkeeper.com$link> $date </a>  $firstActivityString";


      /* ---- Changed starting value of $i to fix activity count.  $i represents the current 
         activity and we need to start at two since first activity was done separately 
         from this loop.   ---- */
      $i = 2;


      foreach($runkeeperHtml->find('div[class=activityMonth menuItem ]') as $activity)
      {

	/* ----  Get activity link ---- */
        $beginstr = "link=";
        $endstr = ">";
        $beginstrpos = (strpos($activity,$beginstr) + 6);
        $endstrpos = (strpos($activity,$endstr) - 1);
        $endstrpos = $endstrpos - $beginstrpos;
        $link = substr($activity,$beginstrpos,$endstrpos);

	/* ----  Get day from image src link ---- */ 
        preg_match('/\-\d{1,2}/', $activity->find('div[class=day]', 0)->innertext, $matchday);
        $day=$matchday[0];
        preg_match('/\d{1,2}/', $day, $matchday);


	/* ---- If there are no more activities left, shift arrays so next month's date 
           and next month's activities are at key [0]   ---- */
        if ($item_arr[0] == 1)
        {
           array_shift($month_arr);
           array_shift($item_arr);
        }
        else
        {
	  /* ---- We have what we need from this activity so decrement items in item_array. ---- */
          $item_arr[0]--;
        }

        $date = "<br> $matchday[0] $month_arr[0] ";

	/* ---- ActivityString is changed a little to accomodate link and date ---- */
        $activityString = $activity->find('div[class=mainText]', 0)->innertext;
        $activityString .= ' ';
        $activityString .= $activity->find('div[class=distance]', 0)->innertext;
        $activityString .= ' ';
        $activityString .= $activity->find('div[class=distanceUnit]', 0)->innertext;
        echo "<a  target=_blank href=http://www.runkeeper.com$link> $date </a>  $activityString";

        $activityString = '';

	/* ---- Changed comparison operator to fix activity count ---- */
        if ($i == get_option('runkeeper_widget_activity_count'))
        {
           break;
        }
        $i++;
      }

      echo '</i>';
    }

    echo $args['after_widget'];
  }

  /* ---- Register Function ---- */
  /* ---- Function that wordpress uses to register the widget, setup is done here. ---- */
  function register(){
    register_sidebar_widget('Runkeeper', array('Runkeeper_Widget', 'widget'));
    register_widget_control('Runkeeper', array('Runkeeper_Widget', 'control'));
  }
}
?>
