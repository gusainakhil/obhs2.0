<header class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white sticky top-0 z-30 shadow-md">
            <div class="flex items-center justify-between px-3 lg:px-5 h-12">

                <div class="flex items-center space-x-3">
                    <button id="menuToggle" class="lg:hidden text-white hover:bg-white/20 p-1.5 rounded-md transition">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                    <h1 class="text-base lg:text-lg font-semibold">Home - <?php echo $station_name;
                    ?></h1>
                </div>

                <a href="logout.php"
                    class="bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded-md text-xs font-medium transition shadow inline-block">
                    <i class="fas fa-sign-out-alt mr-1"></i>Sign Out
                </a>

            </div>
        </header>