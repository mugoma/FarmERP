<h1>Forms Dashboard</h1>
<div class="jumbotron jumbotron-fluid">
    <div class="container">
        <h1 class="display-4">Welcome <?= $_SESSION['username']?></h1>
        <p class="lead">Today is <?= date("l, jS F Y")?></p>
    </div>
</div>

<div class='row'>
<div class='col-sm-12'>
    <div class="card border-dark text-center mb-4">
        <div class="card-header text-white bg-dark">
            System Auth
        </div>
        <ul class="list-group list-group-flush">
        <li class="list-group-item"><a href=" /auth/registration.html">Add System User</a></li>
        <li class="list-group-item"><a href=" /auth/edit-user.html">Edit System User</a></li>
        <li class="list-group-item"><a href=" /auth/password_change.html">Change My Password</a></li>
        </ul>
    </div>
</div>

</div>



