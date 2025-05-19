<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre compte recruteur</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <h1 style="color: #2c3e50;">Bonjour {{ $recruiterName }},</h1>
    <p>Voici votre code de vérification : <strong style="font-size: 20px; color: #e74c3c;">{{ $verificationCode }}</strong></p>
    <p>Veuillez entrer ce code pour activer votre compte.</p>
    <p>Cordialement,</p>
    <p>L'équipe de recrutement.</p>
    <p>Talent Match</p>
</body>
</html>
