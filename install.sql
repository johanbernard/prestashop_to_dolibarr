ALTER TABLE `PREFIX_product`
  ADD date_export_doli datetime,
  ADD id_ext_doli int,
  ADD ref_doli varchar(32),
  ADD reference_old_doli varchar(32) DEFAULT 'FIRST_INIT';
  
ALTER TABLE `PREFIX_product_attribute`
  ADD date_export_doli datetime,
  ADD id_ext_doli int,
  ADD ref_doli varchar(32),
  ADD reference_old_doli varchar(32) DEFAULT 'FIRST_INIT';
  
ALTER TABLE `PREFIX_customer`
  ADD date_export_doli datetime,
  ADD id_ext_doli int;
   
ALTER TABLE `PREFIX_orders`
  ADD date_export_order_doli datetime,
  ADD id_ext_order_doli int,
  ADD date_export_invoice_doli datetime,
  ADD id_ext_invoice_doli int;
  
ALTER TABLE `PREFIX_order_state`
  ADD id_order_state_doli int;