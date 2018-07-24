describe("Api", function() {
  var apiUrl;

  beforeEach(function() {
	  apiUrl=window.apiUrl;
  });

  it("should be able to save a document", function() {
    var doc={
		_id:'new doc',
		a:'A',
		b:'B',
		bool:true
	};
	return fetch(apiUrl,{
		method:'POST',
		
		body:{doc:JSON.stringify(doc),table:'table1'}
	})
	.then(res =>{ 
		console.log(res);
		return res.text();
	})
	.then(res => {
		console.log('res',res);
		//expect(res._rev).toBeDefined;
	});

  });

});
