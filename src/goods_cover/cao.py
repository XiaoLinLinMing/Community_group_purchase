import base64
image_path = '123.jpg'
with open(image_path, 'rb') as f:
	image = f.read()
	image_base64 = str(base64.b64encode(image), encoding='utf-8')
	print(image_base64)