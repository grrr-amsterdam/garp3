var tests = [{
	input:  '<br>bla',
	output: '<p>bla</p>'
},{
	input:  '<p>bla</p>',
	output: '<p>bla</p>'
},{
	input:  '<b>bold</b>',
	output: '<p><b>bold</b></p>'
},{
	input:  '<p>br<br></p>',
	output: '<p>br</p>'	
},{
	input:  '<p>br<br><br></p>',
	output: '<p>br</p>'
},{
	input:  '<p>Contrary to popular belief, Lorem Ipsum is<br>a<br>b<br>c<br><br>def<br><br><br><li></li> not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source.</p><dl class="figure" style="float: left;"><dt><img src="sneeuw.jpg"> </dt><dd>Sneeuw</dd></dl><p>Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of "de Finibus Bonorum et Malorum" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance.The first line of Lorem Ipsum, "Lorem ipsum dolor sit amet..", comes from a line in section 1.10.32.</p><p>beer<br>bla<br><br>aap</p>',
	output: '<p>Contrary to popular belief, Lorem Ipsum is<br>a<br>b<br>c<br>def</p><li></li> not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source.<dl class="figure" style="float: left;"><dt><img src="sneeuw.jpg"> </dt><dd>Sneeuw</dd></dl><p>Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of "de Finibus Bonorum et Malorum" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance.The first line of Lorem Ipsum, "Lorem ipsum dolor sit amet..", comes from a line in section 1.10.32.</p><p>beer<br>bla<br>aap</p>'
},{
	input: '<p>Contrary to popular belief, Lorem Ipsum is<br>a<br>b<br>c<br>def</p><li></li> not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source.<dl class="figure" style="float: left;"><dt><img src="sneeuw.jpg"> </dt><dd>Sneeuw</dd></dl><p>Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of "de Finibus Bonorum et Malorum" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance.The first line of Lorem Ipsum, "Lorem ipsum dolor sit amet..", comes from a line in section 1.10.32.</p><p>beer<br>bla<br>aap</p>',
	output: '<p>Contrary to popular belief, Lorem Ipsum is<br>a<br>b<br>c<br>def</p><li></li> not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source.<dl class="figure" style="float: left;"><dt><img src="sneeuw.jpg"> </dt><dd>Sneeuw</dd></dl><p>Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of "de Finibus Bonorum et Malorum" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance.The first line of Lorem Ipsum, "Lorem ipsum dolor sit amet..", comes from a line in section 1.10.32.</p><p>beer<br>bla<br>aap</p>'
}];
/*
tests = [{
	input: '<p>Contrary to popular belief, Lorem Ipsum is<br>a<br>b<br>c<br><br>def<br><br><br><li></li> not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source.</p><dl class="figure" style="float: left;"><dt><img src="sneeuw.jpg"> </dt><dd>Sneeuw</dd></dl><p>Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of "de Finibus Bonorum et Malorum" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance.The first line of Lorem Ipsum, "Lorem ipsum dolor sit amet..", comes from a line in section 1.10.32.</p><p>beer<br>bla<br><br>aap</p>',
	output: '<p>Contrary to popular belief, Lorem Ipsum is<br>a<br>b<br>c<br>def</p><li></li> not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source.<dl class="figure" style="float: left;"><dt><img src="sneeuw.jpg"> </dt><dd>Sneeuw</dd></dl><p>Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of "de Finibus Bonorum et Malorum" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance.The first line of Lorem Ipsum, "Lorem ipsum dolor sit amet..", comes from a line in section 1.10.32.</p><p>beer<br>bla<br>aap</p>'
}]
var tests = [{
	input: '<br>bla',
	output: '<p>bla</p>'
}];*/
