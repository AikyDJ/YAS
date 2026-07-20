<!DOCTYPE html>
<html lang="en"
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
</head>
<body>

<h2>Connexion</h2>

<form action="" method="POST">
    <div>
        <label for="telephone">Numéro de téléphone :</label>
        <input type="text" name="telephone" id="telephone" required>
    </div>

    <br>

    <div>
        <label for="code_secret">Code secret :</label>
        <input type="password" name="code_secret" id="code_secret" maxlength="4" required>
    </div>

    <br>

    <button type="submit">Se connecter</button>
</form>

</body>
</html>