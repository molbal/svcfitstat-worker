<?php
    // Set JSON header
    header('Content-Type: application/json');
    try {

        // Get defaults
        $max_length = getenv("SFS_FIT_MAX_LENGTH") == "" ? 2048 : intval(getenv("SFS_FIT_MAX_LENGTH"));
        $python_binary = 'xvfb-run python3.6';
        $pyfa_main = '/pyfa/pyfa.py';
        $time_limit = getenv("SFS_MAX_EXEC_TIME") == "" ? 15 : intval(getenv("SFS_MAX_EXEC_TIME"));
        $additional_cmd = getenv("SFS_ADDITIONAL_CMD") == "" ? "-r -l Critical" : getenv("SFS_ADDITIONAL_CMD");
        $api_secret = getenv("SFS_SECRET") == "" ? null : getenv("SFS_SECRET");

        set_time_limit($time_limit);

        $secret = $_POST["secret"] ?? $_GET["secret"];
        if ($api_secret && trim($secret) != trim($api_secret)) {
            throw new Exception("Invalid secret. Please provide the secret specified in the SFS_SECRET environment variable.", 403);
        }
        
        // Get fit, accept both POST and url encoded GET
        $fit = $_POST["fit"] ?? urldecode($_GET["fit"]);
        if (!$fit) {
            throw new Exception("Error Processing Request: Please supply a fit. For more details please check https://github.com/molbal/svcfitstat-worker", 400);
        }

        // Let's not overload this
        if (strlen($fit) > $max_length) {
            throw new Exception("Input data is too long (Max length: $max_length)", 400);
        }

        // Build the shell command
        $fit_b64 = base64_encode(trim($fit));
        $command = sprintf("%s %s %s -f %s", $python_binary, $pyfa_main, $additional_cmd, $fit_b64);

        // Run the command
        $ret = shell_exec($command);

        // See if it returned anything
        if (!$ret) {
            throw new Exception("Internal error inside the container: Binary command '$command' had no response.", 500);
        }

        $stats = "{".(explode("{", $ret, 2)[1]);

        $success = true;
        if (stripos($ret, "error") !== false) {
            $success = false;
        }

        // Return the fit
        echo json_encode([
            "success" => $success,
            "stats" => json_decode($stats, 1),
            "debug" => [
                "rawoutput" => $ret,
                "fit" => $fit,
                "command" => $command
            ]
        ]);

    }
    catch (Exception $e) {
        // Handle the errors
	    http_response_code($e->getCode() ?? 500);
		echo json_encode(["success" => false, "errorText" => $e->getMessage(), "errorCode" => $e->getCode()]);
	}

?>