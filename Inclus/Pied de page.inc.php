<?php
echo '</section>';
echo '<footer id="Pied-de-page">';
echo '<p>Matthieu Laurent | Grégoire Grzeczkowicz</p>';
echo '</footer>';

//scripts
echo '<script>if (\'serviceWorker\' in navigator) {navigator.serviceWorker.register(\'service-worker.js\').catch(function(err) {console.log("Service worker no register. This happened: ", err)});}</script>';

echo '</body>';
echo '</html>';
?>