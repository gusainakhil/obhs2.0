      <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#"
                role="button">
                <i class="bi bi-list"></i>
              </a>
            </li>
            <li class="nav-item d-none d-md-block"><a href="#"
                class="nav-link">Home</a></li>
            <li class="nav-item d-none d-md-block"><a href="#"
                class="nav-link">Contact</a></li>
          </ul>
          <!--end::Start Navbar Links-->
          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">
            <!--begin::Navbar Search-->
            <li class="nav-item">
              <a class="nav-link" data-widget="navbar-search" href="#"
                role="button">
                <i class="bi bi-search"></i>
              </a>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit"
                  style="display: none"></i>
              </a>
            </li>

            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle"
                data-bs-toggle="dropdown">
                <img
                  src="./assets/img/logo-white.png"
                  class="user-image rounded-circle shadow"
                  alt="User Image" />
                <span class="d-none d-md-inline">Beatle Analytics</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <!--begin::User Image-->
                <!--end::Menu Body-->
                <!--begin::Menu Footer-->
                <li class="user-footer">

                  <a href="logout.php" class="btn btn-default btn-flat">Sign
                    out</a>
                </li>
                <!--end::Menu Footer-->
              </ul>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->
      <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <!--begin::Brand Link-->
          <a href="./index.php" class="brand-link">
            <!--begin::Brand Image-->
            <img
              src="./assets/img/AdminLTELogo.png"
              alt="AdminLTE Logo"
              class="brand-image opacity-75 shadow" />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">OBHS</span>
            <!--end::Brand Text-->
          </a>
          <!--end::Brand Link-->
        </div>
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul
              class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview"
              role="navigation"
              aria-label="Main navigation"
              data-accordion="false"
              id="navigation">
              <li class="nav-item menu-open">
                <a href="./index.php" class="nav-link active">
                  <i class="nav-icon bi bi-speedometer"></i>
                  <p>
                    Dashboard
                    
                  </p>
                </a>
              </li>

              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-broadcast-pin"></i> 
                    Stations
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="add-station.php" class="nav-link">
                      <i class="nav-icon bi bi-circle"></i>
                      <p> Create Station</p>
                    </a>
                  </li>
                </ul>
                 <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="list-stations.php" class="nav-link">
                      <i class="nav-icon bi bi-circle"></i>
                      <p> List Stations</p>
                    </a>
                  </li>
                </ul>
              </li>
                <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-people"></i>    
                    users
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="create-user.php" class="nav-link">
                      <i class="nav-icon bi bi-circle"></i>
                      <p> Add Users</p>
                    </a>
                  </li>
                </ul>
                 <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="user-list.php" class="nav-link">
                      <i class="nav-icon bi bi-circle"></i>
                      <p> List Users</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-bookmark-check"></i>   
                  <p>   Marking 
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                 <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="update-calculation.php" class="nav-link">
                      <i class="nav-icon bi bi-circle"></i>
                      <p> Update Calculation</p>
                    </a>
                    <a href="list-markings.php" class="nav-link">
                      <i class="nav-icon bi bi-circle"></i>
                      <p> List Markings</p>
                    </a>
                  </li>
                </ul>
              </li>
               <!-- <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-megaphone"></i>  
                      Payment Alert
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="station-payment-alert.php" class="nav-link">
                      <i class="nav-icon bi bi-circle"></i>
                      <p> Payment Alert</p>
                    </a>
                  </li>
                </ul>
                 
              </li> -->


               <!-- <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-megaphone"></i>  
                      Advertisment
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="global-advertisment-list.php" class="nav-link">
                      <i class="nav-icon bi bi-circle"></i>
                      <p>  Advertisment List</p>
                    </a>
                  </li>
                </ul>
                 <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="Global-advertisment.php" class="nav-link">
                      <i class="nav-icon bi bi-circle"></i>
                      <p> Global Advertisment</p>
                    </a>
                  </li>
                </ul>
              </li> -->
              
            </ul>
            <!--end::Sidebar Menu-->
          </nav>
        </div>
        <!--end::Sidebar Wrapper-->
      </aside>