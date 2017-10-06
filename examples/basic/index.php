<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../../AltoRouter.php';

$router = new AltoRouter();
$router->setBasePath('/AltoRouter/examples/basic');
$router->map('GET|POST','/', 'home#index', 'home');
$router->map('GET','/users/', array('c' => 'UserController', 'a' => 'ListAction'));
$router->get('/users/[i:id]', 'users#show', 'users_show');
$router->post('/users/[i:id]/[delete|update:action]', 'usersController#doAction', 'users_do');

// match current request
$match = $router->match();
?>
<h1>AltoRouter</h1>

<h3>Current request: </h3>
<pre>
    <?php
    foreach($match as $key => $value) {
      echo '<p>' . $key . ': ';
      if(is_array($value)) {
          echo '<ul>';
            foreach($value as $k => $v) {
                echo '<li>'.$k.': '.$v.'</li>';
            }
          echo '</ul>';
      }
      else {
          echo $value;
      }
      echo '</p>';
    }
    ?>
</pre>

<h3>Try these requests: </h3>
<p><a href="<?php echo $router->generate('home'); ?>">GET <?php echo $router->generate('home'); ?></a></p>
<p><a href="<?php echo $router->generate('users_show', array('id' => 5)); ?>">GET <?php echo $router->generate('users_show', array('id' => 5)); ?></a></p>
<p><form action="<?php echo $router->generate('users_do', array('id' => 10, 'action' => 'update')); ?>" method="post"><button type="submit"><?php echo $router->generate('users_do', array('id' => 10, 'action' => 'update')); ?></button></form></p>
