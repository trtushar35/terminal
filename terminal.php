<?php

$valid_username = 'tushar';
$valid_password = 'tushar';

session_start();

function clear_command()
{
    if (isset($_SESSION['logged_in'])) {
        $logged_in = TRUE;
    } else {
        $logged_in = FALSE;
    }
    session_unset();
    if ($logged_in) {
        $_SESSION['logged_in'] = TRUE;
    }
}

if (!isset($_SESSION['save_commands']) || !isset($_SESSION['commands'])) {
    $_SESSION['save_commands'] = array();
    $_SESSION['commands'] = array();
    $_SESSION['command_responses'] = array();
}

$toggling_save = FALSE;
$toggling_current_save_command = FALSE;

if (isset($_POST['clear']) && $_POST['clear'] == 'clear') {
    clear_command();
}

if (isset($_POST['save_command_id']) && is_numeric($_POST['save_command_id'])) {
    $toggling_save = TRUE;
    $save_command_id = (int) $_POST['save_command_id'];
    if (count($_SESSION['save_commands']) == $save_command_id) {
        $toggling_current_save_command = TRUE;
    } else {
        $_SESSION['save_commands'][$save_command_id] = !$_SESSION['save_commands'][$save_command_id];
    }
}

$previous_commands = '';
foreach ($_SESSION['save_commands'] as $index => $save) {
    if ($save) {
        $current_command = $_SESSION['commands'][$index];
        if ($current_command != '') {
            $previous_commands .= $current_command . '; ';
        }
    }
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!isset($_SESSION['logged_in'])) {

        if ($username === $valid_username && $password === $valid_password) {
            $_SESSION['logged_in'] = TRUE;
            $response = array('Welcome to terminal !!');
        } else {
            $response = array('Incorrect Username or Password');
        }
        array_push($_SESSION['save_commands'], FALSE);
        array_push($_SESSION['commands'], 'Login Attempt: ' . $username);
        array_push($_SESSION['command_responses'], $response);
    }
} else {

    if (isset($_POST['command']) && isset($_SESSION['logged_in'])) {
        $command = trim($_POST['command']);

        if ($command !== '' && !$toggling_save) {
            if ($command === 'logout') {
                session_unset();
                $response = array('Successfully Logged Out');
            } elseif ($command === 'clear') {
                clear_command();
            } else {
                exec($previous_commands . $command . ' 2>&1', $response, $error_code);
                if ($error_code > 0 && empty($response)) {
                    $response = array('Error');
                }
            }
        } else {
            $response = array();
        }

        if ($command !== 'logout' && $command !== 'clear') {
            if ($toggling_save) {
                if ($toggling_current_save_command) {
                    array_push($_SESSION['save_commands'], TRUE);
                    array_push($_SESSION['commands'], $command);
                    array_push($_SESSION['command_responses'], $response);
                    if ($command !== '') {
                        $previous_commands .= $command . '; ';
                    }
                }
            } else {
                array_push($_SESSION['save_commands'], FALSE);
                array_push($_SESSION['commands'], $command);
                array_push($_SESSION['command_responses'], $response);
            }
        }
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>PHP Terminal</title>
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #1e1e1e;
            color: #c7c7c7;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            text-align: center;
        }

        input,
        textarea {
            color: #c7c7c7;
            font-family: inherit;
            font-size: inherit;
            background-color: #2d2d2d;
            border: 1px solid #5a5a5a;
            border-radius: 3px;
            padding: 5px;
            outline: none;
        }

        input:focus,
        textarea:focus {
            border-color: #00FF00;
            background-color: #3e3e3e;
        }

        .content {
            width: 80%;
            max-width: 800px;
            margin: 40px auto;
            text-align: left;
            overflow: hidden;
            border: 1px solid #00FF00;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 255, 0, 0.5);
        }

        .terminal {
            height: 500px;
            position: relative;
            overflow: auto;
            padding: 10px;
            background-color: #2d2d2d;
            border-radius: 5px;
        }
            
        .terminal .bar {
            border-bottom: 1px solid #00FF00;
            padding: 5px;
            font-size: 18px;
        }

        .terminal .commands {
            padding: 2px;
            padding-right: 0;
        }

        .terminal #command {
            width: 90%;
            margin-top: 5px;
        }

        .terminal .colorize {
            color: #00FF00;
        }

        .terminal .save_button {
            float: right;
            border-width: 1px;
            border-style: solid;
            border-color: #00FF00;
            padding: 2px 5px;
            margin-left: 5px;
            background-color: transparent;
            cursor: pointer;
            border-radius: 3px;
            transition: background-color 0.3s;
        }

        .terminal .save_button:hover {
            background-color: rgba(0, 255, 0, 0.2);
        }

        pre {
            margin: 0;
            white-space: pre-wrap;
            /* Preserve whitespace */
        }
    </style>
</head>

<body>
    <div class="content">
        <div class="terminal" onclick="document.getElementById('username').focus();" id="terminal">
            <div class="bar text-center">
                <h4>Terminal</h4>
            </div>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="commands" id="commands">
                <input type="hidden" name="save_command_id" id="save_command_id" />
                <?php if (!empty($_SESSION['commands'])) { ?>
                    <div>
                        <?php foreach ($_SESSION['commands'] as $index => $command) { ?>
                            <input type="button" value="<?php echo $_SESSION['save_commands'][$index] ? 'Un-save' : 'Save'; ?>"
                                onfocus="this.style.color='#0000FF';" onblur="this.style.color='';"
                                onclick="toggle_save_command(<?php echo $index; ?>);" class="save_button" />
                            <pre><?php echo '$ ', $command, "\n"; ?></pre>
                            <?php foreach ($_SESSION['command_responses'][$index] as $value) { ?>
                                <pre><?php echo htmlentities($value), "\n"; ?></pre>
                            <?php } ?>
                        <?php } ?>
                    </div>
                <?php } ?>
                <?php if (!isset($_SESSION['logged_in'])) { ?>
                    Username: <input type="text" name="username" id="username" placeholder="Username" autocomplete="off"
                        required />
                    Password: <input type="password" name="password" id="password" placeholder="Password" autocomplete="off"
                        required />
                    <input type="submit" value="Login" />
                <?php } else { ?>
                    <input type="text" name="command" id="command" autocomplete="off"
                        onkeydown="return command_keyed_down(event);" />
                    <input type="button" value="Save Command" onfocus="this.style.color='#0000FF';"
                        onblur="this.style.color='';" onclick="toggle_save_command(<?php if (isset($_SESSION['commands'])) {
                            echo count($_SESSION['commands']);
                        } else {
                            echo 0;
                        } ?>);" class="save_button" />
                <?php } ?>
            </form>
        </div>
    </div>
    <script type="text/javascript">
        function toggle_save_command(save_command_id) {
            document.getElementById('save_command_id').value = save_command_id;
            document.getElementById('commands').submit();
        }

        function command_keyed_down(event) {
            if (event.key === 'Enter') {
                document.getElementById('commands').submit();
                return false;
            }
        }

        window.onload = function () {
            // Check if the username field exists first
            if (document.getElementById('username')) {
                document.getElementById('username').focus();
            } else if (document.getElementById('command')) {
                document.getElementById('command').focus();
            }
        };
    </script>

</body>

</html>