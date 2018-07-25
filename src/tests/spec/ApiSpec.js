describe("Api", function() {
  var apiUrl;

  beforeEach(function() {
	  apiUrl=window.apiUrl;
  });

  it("should be able to save a document", function() {
    var doc={
		_id:'new doc '+new Date().getTime(),
		a:'A',
		b:'B',
		bool:true
	};
	return fetch(apiUrl,{
		method:'POST',
		body:JSON.stringify({doc:doc,table:'table2'})
	})
	.then(res =>{ 
		console.log(res);
		return res.text();
	})
	.then(res => {
		console.log('res',res);
		expect(res._rev).toBeDefined();
	});

  });

});
