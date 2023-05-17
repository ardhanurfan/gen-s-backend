<!DOCTYPE html>
<html>
<head>
    <title>Gen-S Delete Account</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #282727;
            color: white;
        }

        h1 {
            font-size: xx-large;
        }
        
        .card {
            margin: auto;
            margin-top: 100px;
            max-width: 400px;
            background-color: #1c1b1b;
            padding: 30px;
            border-radius: 10px;
        }
        
        .card-title {
            text-align: center;
        }
        
        .btn-danger {
            background-color: #ED6363;
            border-color: #ED6363;
            display: flex;
            justify-content: center;
            width: 100%;
            margin-top: 48px;
        }
        
        .btn-danger:hover {
            background-color: #dc5c5c;
            border-color: #dc5c5c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <form method="POST" action="{{ route('account-deleted') }}">
                @csrf
                <h1 class="card-title">Gen-S<br>Delete Account</h1>
    
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
    
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
    
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
    
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete your account?')">Delete Account</button>
    
                @if(session('error'))
                    <div class="alert alert-danger mt-3">
                        {{ session('error') }}
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
