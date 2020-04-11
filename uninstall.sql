ALTER TABLE `PREFIX_product`
  DROP COLUMN date_export_doli,
  DROP COLUMN id_ext_doli,
  DROP COLUMN ref_doli,
  DROP COLUMN reference_old_doli;
  
ALTER TABLE `PREFIX_product_attribute`
  DROP COLUMN date_export_doli,
  DROP COLUMN id_ext_doli,
  DROP COLUMN ref_doli,
  DROP COLUMN reference_old_doli;
  
ALTER TABLE `PREFIX_customer`
  DROP COLUMN date_export_doli,
  DROP COLUMN id_ext_doli;
  
ALTER TABLE `PREFIX_orders`
  DROP COLUMN date_export_order_doli,
  DROP COLUMN id_ext_order_doli,
  DROP COLUMN date_export_invoice_doli,
  DROP COLUMN id_ext_invoice_doli;
  
 ALTER TABLE `PREFIX_order_state`
  DROP COLUMN id_order_state_doli;