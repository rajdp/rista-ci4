<?php ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Edquill</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="favicon-Web-32.ico" id="appIcon" rel="icon" type="image/x-icon">
    <style>
        .edquill-main-div {
            background-image: url('<?php echo base_url(). 'application/modules/v1/views/bg-image.PMW2UP2R.png' ; ?>');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .edquill-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 100vw;
            min-height: 100vh;
            width: auto;
            height: auto;
        }

        .pop-up-background {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 100;
        }

        .pop-up {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            width: 30%;
            height: 100px;
            border-radius: 10px;
        }

        .loader {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 4px solid transparent;
            border-top: 4px solid #8F008A;
            border-right: 4px solid #7A007A;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .pop-up-content {
            font-size: 16px;
            margin-left: 20px;
        }
    </style>
</head>

<body style="margin: 0px !important;">
    <div class="edquill-main-div">
        <div class="edquill-wrapper">
            <div class="pop-up-background">
                <div class="pop-up">
                    <div class="loader"></div>
                    <span class="pop-up-content">Google login in progress.... Please wait!</span>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>

</html>
<?php ?>