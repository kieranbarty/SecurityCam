<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <!-- refreshes page every 120 seconds" -->
    <meta http-equiv="refresh" content="120">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="./css/index.css">
    <title>CMP408 App</title>
</head>

<body>
    <h1 class="display-4">CMP408 - PHP WebUI for RPi Motion Sensor Camera</h1>
    <p class="lead">Here you can see the most recent images taken by the camera, and enlarge them.</p>
    <?php
    require './vendor/autoload.php';

    use Aws\Crypto\Polyfill\Key;
    use Aws\S3\S3Client;
    use Aws\Exception\AwsException;
    use Aws\S3\Exception\S3Exception;

    // bucket name
    $bucketName = 'klb-rpi-bucket';

    // array of object keys
    $keysArr = [];

    // instantiates S3 client
    $s3Client = new S3Client([
        'profile' => 'default',
        'region' => 'us-east-1',
        'version' => '2006-03-01'
    ]);

    // iterates through every single object in the bucket
    try {
        $results = $s3Client->getPaginator('ListObjects', [
            'Bucket' => $bucketName
        ]);

        foreach ($results as $result) {
            foreach ($result['Contents'] as $object) {
                array_push($keysArr, $object['Key']);
            }
        }
    } catch (S3Exception $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    $revKeysArr = array_reverse($keysArr);

    function requestUrl($s3Client, $key)
    {
        //Get a command to GetObject
        $cmd = $s3Client->getCommand('GetObject', [
            'Bucket' => 'klb-rpi-bucket',
            'Key'    => $key
        ]);
        // creates a request for a presigned URL lasting 10 minutes
        $request = $s3Client->createPresignedRequest($cmd, '+10 minutes');
        // gets the url as a string
        $signedUrl = (string) $request->getUri();
        return $signedUrl;
    }

    ?>
    <!-- image gallery, populated via PHP AWS SDK and Amazon S3 -->
    <div class="split left">
        <p class="lead">Below are all of the images currently stored, taken via the security camera</p>
        <div class="row">
            <?php
            // for every key, echos an img with the keys presigned url
            // creates a new array via reversing the $keysArr, results in most recently added item to object displaying first.
            foreach ($revKeysArr as $key) {
                $signedUrl = requestUrl($s3Client, $key);
                echo '<div class="column">';
                echo '<img src = "' . $signedUrl  . '"alt="' . substr($key, 0, -4) . '" onclick=selectImage(this);>';
                echo '<label for="column" style="color: white; display: block; text-align: center">' . $key . '</label><br>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="split right">
        <p class="lead">This is the most recent image.</p>
        <div class="recentImage">
            <?php
            // echos the most recent image in S3 bucket
            echo '<img src = "' . requestUrl($s3Client, $revKeysArr[0]) . '">';
            echo '<label for="column" style="color: white; display: block; text-align: center">' . $revKeysArr[0] . '</label><br>';
            ?>
        </div>

        <div class="expandedImage">
            <p class="lead">This is the expanded image</p>
            <img id="expanded" />
            <div id="imagetext"></div>
        </div>
    </div>

    <script>
        function selectImage(image) {
            var expand = document.getElementById("expanded");
            var desc = document.getElementById("imagetext");
            expanded.src = image.src;
            desc.innerHTML = image.alt;
            expand.parentElement.style.display = "block";
        }
    </script>

</body>

</html>