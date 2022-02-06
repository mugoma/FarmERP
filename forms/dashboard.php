
                    <h1>Forms Dashboard</h1>

                    <div class="jumbotron jumbotron-fluid">
						<div class="container">
							<h1 class="display-4">Welcome <?= $_SESSION['username']?></h1>
							<p class="lead">Today is <?= date("l, jS F Y")?></p>
						</div>
					</div>

				<div class='row'>
					<div class='col-sm-12 col-lg-3'>
						<div class="card border-dark text-center mb-4">
							<div class="card-header text-white bg-dark">
								Edit
							</div>
							<ul class="list-group list-group-flush">
							<li class="list-group-item"><a href="/forms/edit/product.html">Products</a></li>
							<li class="list-group-item"><a href="/forms/edit/process.html">Process</a></li>
							<li class="list-group-item"><a href="/forms/edit/bank-account.html">Bank Account</a></li>
							<li class="list-group-item"><a href="/forms/edit/retail.html">Retail Unit</a></li>
							</ul>
						</div>
					</div>
					<div class='col-sm-12 col-lg-3'>
						<div class="card border-dark text-center mb-4">
							<div class="card-header text-white bg-dark">
								Add
							</div>
							<ul class="list-group list-group-flush">
								<li class="list-group-item"><a href="/forms/add/product.html">Product</a></li>
								<li class="list-group-item"><a href="/forms/add/process.html">Process</a></li>
								<li class="list-group-item"><a href="/forms/add/worker.html">Worker</a></li>
								<li class="list-group-item"><a href="/forms/add/bank-account.html">Bank Account</a></li>
								<li class="list-group-item"><a href="/forms/add/retail.html">Retail Unit</a></li>
								<li class="list-group-item"><a href="/forms/add/farm-building.html">Farm Building</a></li>

							</ul>
						</div>
					</div>
					<div class='col-sm-12 col-lg-3'>
						<div class="card border-dark text-center mb-4">
                            <div class="card-header text-white bg-dark">
								Add Record
                            </div>
                            <ul class="list-group list-group-flush">
								<li class="list-group-item"><a href="/forms/add/purchase.html">Purchase Record</a></li>
								<li class="list-group-item"><a href="/forms/add/plant-animal-purchase.html">Plant/Animal Purchase Record</a></li>
								<li class="list-group-item"><a href="/forms/add/process_record.html">Process Record</a></li>
								<li class="list-group-item"><a href="/forms/add/destroy_record.html">Destroyed Product Record</a></li>
								<li class="list-group-item"><a href="/forms/add/production_record.html">Production Record</a></li>
								<li class="list-group-item"><a href="/forms/add/product_change.html">Product Change Record</a></li>
								<li class="list-group-item"><a href="/forms/add/sale.php">Sale Record</a></li>
								<li class="list-group-item"><a href="/forms/add/delivery-sent.php">Delivery Send Record</a></li>
								<li class="list-group-item"><a href="/forms/add/delivery-received.php">Delivery Receive Record</a></li>
								<li class="list-group-item"><a href="/forms/add/retail-sale.php">Retail Sale Record</a></li>
                            </ul>
						</div>
					</div>
					<div class='col-sm-12 col-lg-3'>
						<div class="card border-dark text-center mb-4">
							<div class="card-header text-white bg-dark">
								Delete
							</div>
							<ul class="list-group list-group-flush">
								<li class="list-group-item"><a href="/forms/delete/product.html">Product</a></li>
								<li class="list-group-item"><a href="/forms/delete/process.html"> Process</a></li>
								<li class="list-group-item"><a href="/forms/delete/worker.html">Worker</a></li>
								<li class="list-group-item"><a href="/forms/delete/retail-unit.html">Retail Unit</a></li>
								<li class="list-group-item"><a href="/forms/delete/bank-account.html">Bank Account</a></li>
							</ul>
						</div>
					</div>

				</div>

		

