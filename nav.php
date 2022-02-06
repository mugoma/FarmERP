<nav class='navbar navbar-expand-lg navbar-light bg-light'>
  <a class='navbar-brand' href='#'>Yengas FarmERP</a>
  <button class='navbar-toggler' type='button' data-toggle='collapse' data-target='#navbarSupportedContent' aria-controls='navbarSupportedContent' aria-expanded='false' aria-label='Toggle navigation'>
    <span class='navbar-toggler-icon'></span>
  </button>

  <div class='collapse navbar-collapse' id='navbarSupportedContent'>
    <ul class='navbar-nav mr-auto nav-pills'>
      <li class='nav-item'>
        <a class='nav-link disabled' href='#'>Home <span class='sr-only'>(current)</span></a>
      </li>
      <li class='nav-item'>
        <a class='nav-link <?=(preg_match('(forms)', $_SERVER['PHP_SELF']))?"active text-white":"";?>' href='/forms'>Forms</a>
      </li> 
      <li class='nav-item'>
        <a class='nav-link  <?=(preg_match('(reports)', $_SERVER['PHP_SELF']))?"active text-white":"";?>' href='/reports'>Reports</a>
      </li>
      <li class='nav-item'>
        <a class='nav-link  <?=(preg_match('(auth)', $_SERVER['PHP_SELF']))?"active text-white":"";?>' href='/auth'>Auth</a>
      </li>
    </ul>
    <ul class='navbar-nav right'>
      <li class='nav-item'>
        <a class='nav-link' href='/auth/logout.html'>Logout</a>
      </li>
    </ul>
  </div>
</nav>