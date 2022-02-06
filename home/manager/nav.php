<?php echo "

<nav class='navbar navbar-expand-lg navbar-light bg-light'>
  <a class='navbar-brand' href='#'>FarmErp</a>
  <button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarSupportedContent' aria-controls='navbarSupportedContent' aria-expanded='false' aria-label='Toggle navigation'>
    <span class='navbar-toggler-icon'></span>
  </button>

  <div class='collapse navbar-collapse' id='navbarSupportedContent'>
    <ul class='navbar-nav mr-auto'>
      <li class='nav-item active'>
        <a class='nav-link' href='/home'>Home <span class='sr-only'>(current)</span></a>
      </li>
      <li class='nav-item'>
        <a class='nav-link' href='/home/manager/reports.php'>Reports</a>
      </li> 
    </ul>
    <ul class='navbar-nav right'>
      <li class='nav-item'>
        <a class='nav-link' href='/auth/logout.php'>Logout</a>
      </li>
    </ul>
  </div>
</nav>
"?>