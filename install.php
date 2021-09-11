<?php
/* =-=-=-= Copyright © 2018 eMarket =-=-=-=  
  |    GNU GENERAL PUBLIC LICENSE v.3.0    |
  |  https://github.com/musicman3/eMarket  |
  =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-= */

ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-F3w7mX95PdgyTmZZMECAngseQB83DfGTowi0iMjiWaeVhAn4FJkqJByhZMI3AhiU" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-/bQdsTh/da6pkI1MST/rWKFNjaCP5gBSY4sEBT38Q/9RBh9AH40zEOg7Hlq2THRZ" crossorigin="anonymous"></script>

<?php
if (!isset($_GET['part'])) {
    $download = gitHubData();
    if ($download !== FALSE) {
        // Download and Unzip eMarket archive
        UnzipArchive(downloadArchive());
        // Copying eMarket files
        copyingFiles();

        // Redirect to part 2
        echo "<script>window.location.href='?part=2';</script>";
    } else {
        echo '<span class="badge bg-dark">No data received from GitHub. Please refresh the page to repeat the installation procedure.</span>&nbsp;';
    }
}

if (isset($_GET['part']) && $_GET['part'] == '2') {
    downloadComposer();
    composerInstall();

    filesRemoving(getenv('DOCUMENT_ROOT') . '/composer.phar');
    filesRemoving(getenv('DOCUMENT_ROOT') . '/install.php');

    // Redirect to install page
    echo "<script>window.location.href='controller/install/';</script>";
}

/**
 * Download composer.phar
 *
 */
function downloadComposer() {
    echo '<span class="badge bg-danger">PART II</span>&nbsp;';
    echo '<span class="badge bg-success">Downloading composer.phar</span>&nbsp;';
    ob_flush();
    flush();

    $file_composer = 'https://getcomposer.org/download/latest-stable/composer.phar';
    $file_name_composer = basename($file_composer);
    file_put_contents(getenv('DOCUMENT_ROOT') . '/' . $file_name_composer, file_get_contents($file_composer));
}

/**
 * Composer install
 *
 */
function composerInstall() {
    echo '<span class="badge bg-success">Installing vendor packages</span>&nbsp;';
    ob_flush();
    flush();
    ob_start();

    // system() command or composer extract
    if (!system('php composer.phar install')) {
        $root = realpath(getenv('DOCUMENT_ROOT'));
        $vendor_dir = $root . '/vendor';
        $composerPhar = new Phar($root . '/composer.phar');
        $composerPhar->extractTo($vendor_dir);
        require_once($vendor_dir . '/vendor/autoload.php');
        putenv('COMPOSER_HOME=' . $vendor_dir . '/bin/composer');

        $params = [
            'command' => 'install'
        ];

        $input = new Symfony\Component\Console\Input\ArrayInput($params);
        $output = new Symfony\Component\Console\Output\BufferedOutput(
                Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL
        );

        $application = new Composer\Console\Application();
        $application->setAutoExit(false);
        $application->run($input, $output);
    }

    ob_end_clean();
}

/**
 * Download eMarket archive
 *
 * @return string Name zip-archive
 */
function downloadArchive() {
    echo '<span class="badge bg-danger">PART I</span>&nbsp;';
    echo '<span class="badge bg-success">Downloading eMarket archive</span>&nbsp;';
    ob_flush();
    flush();
    $file = 'https://github.com/musicman3/eMarket/archive/refs/heads/master.zip';
    $file_name = basename($file);
    file_put_contents(getenv('DOCUMENT_ROOT') . '/' . $file_name, file_get_contents($file));
    return $file_name;
}

/**
 * Unzip eMarket archive
 *
 * @param string $file_name eMarket archive name
 */
function UnzipArchive($file_name) {
    echo '<span class="badge bg-success">Unzipping eMarket archive</span>&nbsp;';
    ob_flush();
    flush();

    $zip = new ZipArchive;
    $res = $zip->open(getenv('DOCUMENT_ROOT') . '/' . $file_name);
    if ($res === TRUE) {
        $zip->extractTo('.');
        $zip->close();
    } else {
        echo '<span class="badge bg-dark">An error has occurred. Please check the permissions for the root directory.</span>&nbsp;';
    }

    filesRemoving($file_name);
}

/**
 * Copying eMarket files
 *
 */
function copyingFiles() {
    echo '<span class="badge bg-success">Copying eMarket files</span>&nbsp;';
    ob_flush();
    flush();

    $source_dir = glob("eMarket*")[0];
    $dest_dir = getenv('DOCUMENT_ROOT');
    if (!file_exists($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }
    $dir_iterator = new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $object) {
        $dest_path = $dest_dir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        ($object->isDir()) ? mkdir($dest_path) : copy($object, $dest_path);
    }

    // Delete files
    echo '<span class="badge bg-success">Cleaning</span>&nbsp;';
    ob_flush();
    flush();

    filesRemoving($source_dir);
}

/**
 * Files removing
 *
 * @param string $path Path
 * @return bool
 */
function filesRemoving($path) {
    if (is_file($path)) {
        return unlink($path);
    }
    if (is_dir($path)) {
        foreach (scandir($path) as $p) {
            if (($p != '.') && ($p != '..')) {
                filesRemoving($path . DIRECTORY_SEPARATOR . $p);
            }
        }
        return rmdir($path);
    }
    return false;
}

/**
 * GitHub Data
 *
 * @return array GitHub latest release data
 */
function gitHubData() {
    $connect = curl_init();
    curl_setopt($connect, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($connect, CURLOPT_HTTPHEADER, ['User-Agent: eMarket']);
    curl_setopt($connect, CURLOPT_URL, 'https://api.github.com/repos/musicman3/eMarket/releases/latest');
    $response_string = curl_exec($connect);
    curl_close($connect);
    if (!empty($response_string)) {
        $response = json_decode($response_string, 1);
        if (isset($response['tag_name'])) {
            return $response['tag_name'];
        }
    } else {
        return FALSE;
    }
}
