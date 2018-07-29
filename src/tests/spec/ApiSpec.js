describe("Api", function() {
  var apiUrl;

  beforeEach(function() {
	  apiUrl=window.apiUrl;
  });

  it("should be able to create a document", function() {
    var doc={
			_id:'new doc ' + new Date().getTime(),
			a:'A',
			b:'B',
			bool:true
		};
		return fetch(apiUrl,{
			method:'POST',
			body:JSON.stringify({doc:doc,table:'table2'})
		})
		.then(res => res.json())
		.then(res => {
			//console.log('res',res);
			expect(res._rev).toBeDefined();
			return fetch(apiUrl+'?_id='+encodeURIComponent(doc._id)+'&table=table2');
		})
		.then(res=>res.json())
		.then(res => {
			//console.log('res',res);
			var retrievedDoc=res.doc;
			expect(retrievedDoc.a).toBe('A');
		});
  });
	it("should be able to update a document", function(){
		var doc = {
			_id:'update doc ' + new Date().getTime(),
			a:'A',
			b:'B',
			bool:true
		};
		var rev='';
		//save the doc
		return fetch(apiUrl,{
			method:'POST',
			body:JSON.stringify({doc:doc,table:'table2'})
		})
		.then(res =>{ 
			//console.log(res);
			return res.json();
		})
		.then(res => {
			//console.log('res',res);
			expect(res._rev).toBeDefined();
			rev=res._rev;
			doc._rev=rev;
			doc.c='C';
			//save the doc again
			return fetch(apiUrl,{
				method:'POST',
				body:JSON.stringify({doc:doc,table:'table2'})
			});
		})
		.then(res => {
			//console.log(res);
			return res.json();
		})
		.then(res => {
			//console.log(' updated res',res._rev);
			expect(res._rev.substring(0,2)).toBe('2-');
			return fetch(apiUrl+'?_id='+encodeURIComponent(doc._id)+'&table=table2');
		})
		.then(res=>res.json())
		.then(res => {
			//console.log('res',res);
			var retrievedDoc=res.doc;
			expect(retrievedDoc.c).toBe('C');
		});
	});
	it("should be able to delete a document", function(){
		var doc={
			_id:'delete me ' + new Date().getTime(),
			a:'A',
			b:'B',
			bool:true
		};
		return fetch(apiUrl,{
			method:'POST',
			body:JSON.stringify({doc:doc,table:'table2'})
		})
		.then(res => res.json())
		.then(res => {
			expect(res._rev).toBeDefined();
			return fetch(apiUrl+'?_id='+encodeURIComponent(doc._id)+'&table=table2');
		})
		.then(res=>res.json())
		.then(res => {
			var retrievedDoc=res.doc;
			expect(retrievedDoc.a).toBe('A');
			return fetch(apiUrl,{
				method:"DELETE",
				body:JSON.stringify({
					doc:{_id:doc._id,_rev:retrievedDoc._rev},
					table:'table2'
				})
			});
		})
		.then(res=>{
			res.text().then(function(r){console.log('DELETE r',r)});
			//now try and retrieve it
			return fetch(apiUrl+'?_id='+encodeURIComponent(doc._id)+'&table=table2');
		})
		.then(res=>{
			console.log('deleted res',res);
			res.text().then(function(r){console.log(r)});
			expect(res.status).toBe(404);
		});
	})
});
