<!doctype html><html><head><title>AutoDeploy Script</title></head></head><body><?php // This script created entirely by Brenton Strine. GPL 3.0 License.

/*
 Assumptions:
  1. You've created a SSH key and put it in GitHub (https://help.github.com/articles/generating-ssh-keys/)
  2. Your server looks like this:
 
     accountroot              (your server root. probably named your username. if you're on shared hosting you can't go above this level)
         private              (note this is a sibling of your web root, (not inside it!) therefore it inaccessible over the internet)
         www                  (your web root, e.g. public_html)
             autodeploy.php   (this script)

  3. Parallel to your web root (in other words, one directory *up* from your web root) there is a folder named `private` which this script can write to.
  4. You've configured the items below
  5. You've set up a web hook in GitHub so that this script runs every time the branch you want to
 */

$deploy_location  = ".";         // Location you want to deploy to. Usually will be "." (web root). For a subdirectory do "subdir" or "subdir/furthersubdir".
$repo             = "YOUR-REPO"; // Name of the  repo  you're deploying from. Must be exact GitHub repo name.
$branch           = "master";    // Name of the branch you're deploying from. Usually will be "master" or name of some other branch.
$folder           = "www";       // Name of a folder within the repo you want to copy. Put "" to copy the entire repo. To only copy from a particular subdir put the path to it, e.g. "www" or "site/production"
$notify           =              // List of emails to send notification to when this script is run
    ["YOURNAME@gmail.com"];
$account          = "YOURTEAM";  // The GitHub account name or team name
$githubDomain     = "github.com";// Will be "github.com" unless you're using GitHub Enterprise


/* ------------------------------------------------------------------------------- */
/* ---------------- no need to configure anything below this line ---------------- */
/* ------------------------------------------------------------------------------- */

// update repo. if it's not there, try to create it.
if(is_dir("../private/autodeploy") && is_dir("../private/autodeploy/.git")){
    $git_output = shell_exec("cd ../private/autodeploy && git pull git@".$githubDomain.":" . $account . "/" . $repo . " " . $branch . "  2>&1");
} else {
    echo "<h3>AutoDeploy Configuration</h3><p>Let's try to clone the repo into <code>private/autodeploy</code>.</p>";
    echo "<pre class='code'>";
    echo "<b>git clone git@".$githubDomain.":" . $account . "/" . $repo . " autodeploy</b><br>";
    echo $git_output = shell_exec("cd ../private && git clone git@".$githubDomain.":" . $account . "/" . $repo . " autodeploy 2>&1");
    echo"</pre>";
    if(strpos($git_output, "fatal") >= 0 && (strpos($git_output, "private mode is enabled") || strpos($git_output, "Host key verification failed") || strpos($git_output, "Permission denied") ) ){
        echo "<p>Oops. Looks like a SSH key isn't set up! Please follow instructions <a href='https://help.github.com/articles/generating-ssh-keys/'>on this page</a> to set up SSH keys for this site and then plug them into GitHub.</p>";
    } else {
        echo "<p>Cool, looks like the repo was cloned! Try reloading and we'll see if this works.</p>";
    }
}
// vars for copying function
$total_copied = 0;
$total_files = 0;
$copied_files = "";

// copy files from repo to destination directory
recurse_copy_newer("../private/autodeploy/" . $folder, $deploy_location);

// copy all contents of a directory to the destination as long as the source file is newer than the destination file
function recurse_copy_newer($src,$dst) {
    global $total_copied, $total_files, $copied_files;
    $dir = opendir($src);
    @mkdir($dst);
    while(($file = readdir($dir)) !== false) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy_newer($src . '/' . $file,$dst . '/' . $file);
            } else {
                $total_files++;
                $from = $src . '/' . $file;
                $to = $dst . '/' . $file;
                if(filemtime($from) > filemtime($to)){
                    copy($from,$to);
                    $total_copied++;
                    $copied_files .= $to . "<br>";
                }
            }
        }
    }
    closedir($dir);
}

// build notification info for email or manual execution of this script
$hostName       = $_SERVER[SERVER_NAME];
$scriptLocation = $_SERVER[SCRIPT_FILENAME];
$sourcePath = "http://$githubDomain/$account/$repo/tree/$branch/$folder";
$copy_to = ($copy_to==".") ? "" : $copy_to;
$percent_copied = ($total_copied / $total_files) * 100;
$headers  = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 7bit\r\n";
$subject = "AutoDeploy from repo $account/" . $repo . " to http://" . $hostName;
$email_body = "A total of <b>$total_copied files were copied</b> from <a href='$sourcePath'>GitHub</a> to <a href='http://$hostName'>$hostName</a>.</p>";
$email_body .= "<p>First, this script did a <code>git pull</code> on branch <code>$branch</code> of <code>$repo</code> GitHub responded:</p>";
$email_body .= "<pre class='code'>$git_output</pre>";
$email_body .= "<p>Then this script copied all new files from <a href='$sourcePath'><code>$sourcePath</code></a> to <a href='$hostName'><code>http://" . $hostName  . "/</code></a>.</p>";
if($percent_copied>0){
    $email_body .= "<p>The following $total_copied files (" . intval($percent_copied) . "% of the repo) were updated.</p>";
    $email_body .= "<pre class='code'>" . $copied_files . "</pre>";
} else {
    $email_body .= "<p><strong>No files were copied</strong>. All files were already present on the server and up to date.</p>";
}
$email_body .= "<hr><p><small>This AutoDeploy script is located at <code>$scriptLocation</code>.</small></p>";
$email_body .= "<style>code,.code {background-color:#9BD;border:dotted 1px grey;border-radius:1px;padding: 1px 2px;}pre.code{overflow:auto;background-color:#88AECD;padding:1em;margin:0 1em;}</style>";

// send notification email
mail(rtrim(implode(',', $notify), ','), $subject, $email_body, $headers);

// output info for manual exexution of this scirpt
echo "<h3>AutoDeploy from repo <code>$sourcePath</code> to <code>http://" . $hostName . "</code></h3><hr>";
echo $email_body; /* */
?>
<style scoped>
    body{
        background-color: #333;
        color: #ddd;
        font-family: sans-serif;
        font-size: 1.5em;
        max-width: 50em;
        margin: 1em auto;
        font-weight: 100;
    }
    a{color:#9bf;}
    b,strong{font-weight: 400;color:#fff;}
    code,pre.code{background-color:rgba(136, 174, 205, .2);}
    code{background-color: #303030;}
    pre b{
        display: block;
        background-color: rgba(0, 0, 0, .2);
        margin: -1em -1em 0 -1em;
        padding: 0.2em 1em;
        border-bottom: dotted 1px #666;
    }
</style>
</body></html>
