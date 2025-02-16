<!DOCTYPE html>
<html>
<head>
	<title>{{ $invoice->invoice_no }}</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
	<style type="text/css">
		
		.p1 {
 		 font-family: Arial, Helvetica, sans-serif;
		  color:rgb(224, 25, 3);
		  font-size: 14pt;
		},
		.p2 {
		font-family: Arial, Helvetica, sans-serif;
		font-size: 9pt;
		}
		
	</style>
	<center class="p1">
		<u>INVOICE</u>		
	</center>

	<center class="p2" >
		No. <?php echo $invoice->invoice_no ?>	
	</center>
	
	<hr>
	
	<table class='table table-borderless table-sm' style="font-size: 8px;">
		<thead>
			<tr>
				<th>
					<table class='table table-borderless table-sm' style="font-size: 8px;">
						<thead>
							<tr>
								<th colspan="2">Kepada : PT.<?php echo $config->nama_singkat ?> INDONESIA</th>							
							</tr> 
							<tr>
								<th colspan="2"><?php echo $config->alamat ?></th>							
							</tr> 
							<tr>
								<th colspan="2"><?php echo $config->telepon ?> , <?php echo $config->email ?></th>							
							</tr> 
							<tr>
								<th colspan="2"></th>				
							</tr>

							<tr>
								<th width="35%">Nama Bengkel</th>
								<td ><?php echo $bengkel->bengkel_name ?></td>   
							</tr> 
							<tr>  
								<th width="35%">Kontak Bengkel</th> 
								<td ><?php echo $bengkel->phone ?></td> 
							</tr> 
							<tr>  
								<th width="35%">Alamat</th> 
								<td ><?php echo $bengkel->address ?></td> 
							</tr> 
							<tr>  
								<th width="35%">PIC Bengkel</th>  
								<td ><?php echo $invoice->create_by ?></td> 
							</tr> 
						
						</thead>
					</table>

				</th>

				<th>
					<table class='table table-borderless table-sm' style="font-size: 8px;">
						<thead>
							<tr>
								<th width="35%">Invoice Nomor</th>
								<td ><?php echo $invoice->invoice_no ?></td>   
							</tr> 
							<tr>  
								<th width="35%">Tanggal Invoice</th> 
								<td ><?php echo $invoice->created_date ?></td> 
							</tr> 
							<tr>  
								<th width="35%">Status</th> 
								<td ><?php echo $invoice->status ?></td> 
							</tr> 
							<tr>   
								<th width="35%">PPH</th>  
								<td ><?php echo "Rp " . number_format($invoice->pph,0,',','.'); ?></td> 
							</tr> 
							<tr>
								<th width="35%">Jasa</th>  
								<td ><?php echo "Rp " . number_format($invoice->jasa_total,0,',','.'); ?></td> 
							</tr> 
							<tr>
								<th width="35%">Part</th>  
								<td ><?php echo "Rp " . number_format($invoice->part_total,0,',','.'); ?></td> 
							</tr> 
							<tr>
								<th width="35%">Total</th>
								<td ><?php echo "Rp " . number_format(($invoice->jasa_total - $invoice->pph) + $invoice->part_total,0,',','.'); ?></td> 
							</tr> 
							
						
						</thead>
					</table>
				</th>

			</tr>
		</thead>
	</table>

      <p class="p2">
		<b>Invoice Detail</b>
	  </p>

	<table class='table table-bordered table-sm' style="font-size: 6pt;">
		<thead>
			<tr>
				
				<th width="15%">Service No</th>  
				<th width="10%">Area</th>   
				<th width="15%">Cabang</th> 
				<th width="12%">Tanggal Service</th>   
				<th width="8%">NOPOL</th>
				<th width="17%">Merk</th>
				<th width="10%">Nama barang</th>  		 
				<th width="8%">Part</th>
				<th width="8%">Jasa</th> 
				<th width="8%">Jumlah</th>   	  				 

			</tr>
		</thead>
		<tbody>
				<?php $i=1; foreach($invoice_detail as $ind) { ?>


				<tr>
					
					<td><?php echo $ind->service_no ?></td> 
					<td><?php echo $ind->area ?></td>  
					<td><?php echo $ind->branch ?></td> 
					<td><?php echo $ind->tanggal_service ?></td>
					<td><?php echo $ind->nopol ?></td> 
					<td><?php echo $ind->type ?></td>
					<td><?php echo $ind->service_name ?></td>  
					<td><?php if($ind->part == NULL) { echo "" ;} else { echo "Rp " . number_format($ind->part,0,',','.'); } ?></td>				                                            
					<td><?php if($ind->jasa == NULL) { echo "" ;} else { echo "Rp " . number_format($ind->jasa,0,',','.'); } ?></td>
					<td><?php echo "Rp " . number_format($ind->jasa+$ind->part,0,',','.'); ?></td>
				</tr>	


				<?php $i++; } ?> 
				<tr>
					<td colspan="4" style="border-bottom-style: hidden;border-left-style: hidden;"></td>
					<th colspan="3">Total</th>
					<td ><?php echo "Rp " . number_format($invoice->part_total,0,',','.'); ?></td>
					<td ><?php echo "Rp " . number_format($invoice->jasa_total,0,',','.'); ?></td>
					<td ><?php echo "Rp " . number_format($invoice->jasa_total + $invoice->part_total,0,',','.'); ?></td>
				</tr>
				<tr>
					<td colspan="4" style="border-bottom-style: hidden;border-left-style: hidden;"></td>
					<th colspan="3">PPH 2%</th>
					<td ></td>
					<td > <?php echo "- Rp " . number_format($invoice->pph,0,',','.'); ?> </td>
					<td ></td>
				</tr>
				<tr>
					<td colspan="4" style="border-bottom-style: hidden;border-left-style: hidden;"></td>
					<th colspan="3">Total</th>
					<td ><?php echo "Rp " . number_format($invoice->part_total,0,',','.'); ?></td>
					<td ><?php echo "Rp " . number_format($invoice->jasa_total - $invoice->pph,0,',','.'); ?></td>
					<td ><?php echo "Rp " . number_format(($invoice->jasa_total - $invoice->pph) + $invoice->part_total,0,',','.'); ?></td>
				</tr>

		
		</tbody>
		
	</table>
{{-- </font> --}}

 
</body>
</html>