
					<h1>Reports Dashboard</h1>
					<div class="jumbotron jumbotron-fluid">
						<div class="container">
							<div class="col-md-4 col-sm-1">
							<h1 class="display-5" >Welcome <?= $_SESSION['username']?></h1>
							</div>
							<p class="lead">Today is <?= date("l, jS F Y")?></p>
						</div>
					</div>

				<div class='row'>
					<div class='col'>
						<div class="card border-dark text-center mb-4">
							<div class="card-header">
								
							</div>
							<ul class="list-group list-group-flush">
							<li class="list-group-item"><a href="/reports/?page=products">Products</a></li>
							<li class="list-group-item"><a href="/reports/?page=process">Process</a></li>
							<li class="list-group-item"><a href="/reports/?page=workers"> Workers</a></li>
							<li class="list-group-item"><a href="/reports/?page=retail-unit"> Retail Unit</a></li>
							</ul>
							<div class="card-header">
								
							</div>
							<ul class="list-group list-group-flush">
							<li class="list-group-item"><a href="/reports/?page=product-quantity-current&param=farm" target="_blank"> Current Product Quantities(Quantities-at-hand, Farm)</a></li>
							<li class="list-group-item"><a href="/reports/?page=product-quantity-current&param=retail" target="_blank"> Current Product Quantities(Quantities-at-hand, Retail)</a></li>
							</ul>
						</div>
					</div>
					<div class='col'>
						<div class="card border-dark text-center mb-4">
							<div class="card-header">
								
							</div>
							<ul class="list-group list-group-flush">
								<li class="list-group-item"><a href="/reports/?page=farm-process-records">Process Record</a></li>
								<li class="list-group-item"><a href="/reports/?page=worker-records">Worker Record</a></li>
								<li class="list-group-item"><a href="/reports/?page=product-records">Product Record</a></li>
								<li class="list-group-item"><a href="/reports/?page=production-records">Production Record</a></li>
								<li class="list-group-item"><a href="/reports/?page=product-quantity-records">Product Quantity Record</a></li>
								<li class="list-group-item"><a href="/reports/?page=plant-animal-process-records">Plant/Animal Process Record</a></li>
							</ul>
						</div>
					</div>
					<div class='col'>
						<div class="card border-dark text-center mb-4">
							<div class="card-header">
								
							</div>
							<ul class="list-group list-group-flush">
								<li class="list-group-item"><a href="/reports/?page=cashbook">Cashbook</a></li>
								<li class="list-group-item"><a href="/reports/?page=purchases"> Purchases</a></li>
								<li class="list-group-item"><a href="/reports/?page=sales">Sales</a></li>
								<li class="list-group-item"><a href="/reports/?page=retail-sales">Retail Sales</a></li>
								<li class="list-group-item"><a href="/reports/?page=deliveries-sent">Delivery Sent Records</a></li>
								<li class="list-group-item"><a href="/reports/?page=deliveries-received">Delivery Received Records</a></li>
								<li class="list-group-item"><a href="/reports/?page=deliveries-lost">Delivery Lost Records</a></li>
								<li class="list-group-item"><a href="/reports/?page=bank-account-transactions">Bank Account Transactions</a></li>
								<li class="list-group-item"><a href="/reports/?page=product-change-records">Product Change Record</a></li>

							</ul>
						</div>
					</div>

				</div>

		

