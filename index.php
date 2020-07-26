<?php

define('CREDENTIAL_FILE', __DIR__ . DIRECTORY_SEPARATOR . '.htpasswds');

$admin        = [];
$credentials  = [];
foreach(file(CREDENTIAL_FILE) as $credential) {
    list($user, $passwd) = explode(':', $credential);
    $user   = trim($user);
    $passwd = trim($passwd);
    if ($user === 'admin') {
        $admin['admin'] = $passwd;
    }
    else {
        $credentials[$user] = $passwd;
    }
}

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === 'application/json') {
    $formData = json_decode(trim(file_get_contents('php://input')), true);
    $response = ['status' => true];

    if (!empty($formData['add'])) {
        $formData['add'] = str_replace(' ', '', $formData['add']);
        $credentials[$formData['add']] = password_hash($formData['passwd'], PASSWORD_BCRYPT);
        saveCredentials($admin, $credentials);
    }

    if (!empty($formData['edit'])) {
        $formData['edit'] = str_replace(' ', '', $formData['edit']);
        $credentials[$formData['edit']] = password_hash($formData['passwd'], PASSWORD_BCRYPT);
        saveCredentials($admin, $credentials);
    }

    if (!empty($formData['delete'])) {
        if (isset($credentials[$formData['delete']])) {
            unset($credentials[$formData['delete']]);
            saveCredentials($admin, $credentials);
        }
        else {
            $response['status'] = false;
        }
    }

    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode($response);
    exit(0);
}

function saveCredentials($admin, $credentials) {
    $htpasswds[] = "admin:{$admin['admin']}";
    foreach ($credentials as $user => $passwd) {
        $htpasswds[] = "{$user}:{$passwd}";
    }

    $fp = fopen(CREDENTIAL_FILE, 'w');
    fwrite($fp, implode(PHP_EOL, $htpasswds));
    fclose($fp);

    $response = ['status' => true, '$htpasswds'=>$htpasswds];
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode($response);
    exit(0);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <title>Manage htpasswds credentials</title>
</head>
<body>
<div class="container-sm">
    <div class="jumbotron">
        <h1 class="display-4">Manage Credentials</h1>
        <p class="lead">htaccess / htpasswds</p>
    </div>
    <div class="row">
        <div class="col-sm">
            <table class="table table-bordered table-condensed table-striped table-hover">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>
                            Actions
                            <div class="float-right">
                                <button type="button" class="btn btn-sm btn-success btn-add">Add user</button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($credentials as $user => $passwd) { ?>
                    <tr id="user-<?php echo $user; ?>">
                        <td><?php echo $user; ?></td>
                        <td>
                            <div class="btn-group" role="group" aria-label="Credential actions">
                                <button data-user="<?php echo $user; ?>" type="button" class="btn btn-sm btn-secondary btn-edit">Change password</button>
                                <button data-user="<?php echo $user; ?>" type="button" class="btn btn-sm btn-danger btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="col-sm">
            <form id="credentialsForm" class="hidden">
                <input type="hidden" id="action" value="" />
                <div class="form-group">
                    <label for="user">Username</label>
                    <input type="text" class="form-control" id="user" aria-describedby="userHelp" placeholder="Username" autocomplete="off" />
                    <small id="userHelp" class="form-text text-muted">Username, aka login</small>
                </div>
                <div class="form-group">
                    <label for="passwd">Password</label>
                    <input type="password" class="form-control" id="passwd" autocomplete="off">
                </div>
                <button type="button" class="btn btn-primary btn-save">Save credentials</button>
            </form>
        </div>
    </div>
</div>
<script integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous" src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous" src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous" src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
<script type="text/javascript">
jQuery('document').ready(function($) {
    $('.btn-add').click(function() {
        reset();
        $('#action').val('add');
        $('#credentialsForm').removeClass('hidden');
    });
    $('.btn-edit').click(function() {
        reset();
        $('#action').val('edit');
        $('#credentialsForm').removeClass('hidden');
        $('#user').val($(this).data('user')).attr('readonly', true);
    });
    $('.btn-delete').click(function() {
        let user = $(this).data('user');
        if (confirm('Are you sure?')) {
            if (crud({delete:user})) {
                $('#user-' + user).remove();
            }
        }
    });
    $('.btn-save').click(function() {
        let user     = $.trim($('#user').val());
        let passwd   = $.trim($('#passwd').val());
        let action   = $('#action').val();
        let formData = {};
        if (action === 'add') {
            if (user === '') {
                alert('Username CANNOT be empty.');
                return;
            }
            formData = {add:user,passwd:passwd}
        }
        if (action === 'edit') {
            formData = {edit:user,passwd:passwd}
        }
        if (passwd === '') {
            alert('Password CANNOT be empty.');
            return;
        }
        if (crud(formData)) {
            reset();
            alert('Credentials successfully saved');
            if (action === 'add') {
                window.location.reload(true);
            }
        }
    });
});
function reset() {
    $('#passwd').val('');
    $('#action').val('');
    $('#user').val('').removeAttr('readonly');
    $('#credentialsForm').addClass('hidden');
    $('.btn-save').removeClass('disabled').removeAttr('disabled');
}
async function crud(formData) {
    $('.btn-save').addClass('disabled').attr('disabled', true);
    let response = await fetch('/users/', {
        method : 'POST',
        headers: {'Content-Type': 'application/json'},
        body   : JSON.stringify(formData),
    })
    .then(res => res.json());
    $('.btn-save').removeClass('disabled').removeAttr('disabled');
    return response.status;
}
</script>
</body>
</html>