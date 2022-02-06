<?php echo "

<nav class='navbar navbar-expand-lg navbar-light bg-light'>
  <a class='navbar-brand' href='#'>FarmErp</a>
  <button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarSupportedContent' aria-controls='navbarSupportedContent' aria-expanded='false' aria-label='Toggle navigation'>
    <span class='navbar-toggler-icon'></span>
  </button>

  <div class='collapse navbar-collapse' id='navbarSupportedContent'>
    <ul class='navbar-nav mr-auto'>
      <li class='nav-item active'>
        <a class='nav-link' href='#'>Home <span class='sr-only'>(current)</span></a>
      </li>
      <li class='nav-item'>
        <a class='nav-link' href='/home/employee/vaccines.php'>Vaccines</a>
      </li> 
      <li class='nav-item'>
        <a class='nav-link' href='/home/employee/coop_maintenance.php'>Coop</a>
      </li>
      <li class='nav-item'>
        <a class='nav-link' href='/home/employee/egg_production.php'>Egg Production</a>
      </li>
      <li class='nav-item'>
        <a class='nav-link' href='/home/employee/transactions.php'>Transactions</a>
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