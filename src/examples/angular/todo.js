angular.module('todo',[])
.factory("ItemApi",function($http){
	var apiUrl='../../api.php';
	var table='sampleTodos';
	return {
		saveItem:function(_item) {
			var data={
				doc:{
					_id:_item._id || new Date().getTime(),
					text:_item.text,
					completed:_item.completed || false
				},
				table:table
			};
			if (_item._rev) {
				data.doc._rev=_item._rev;
			}
			return $http.post(
				apiUrl,
				data
			);
		},
		getItems:function(){
			return $http.get(
				apiUrl+'?table='+encodeURIComponent(table)
			);
		},
		deleteItem:function(_item) {
			return $http.delete(
				apiUrl,
				{
					data:{
						doc:_item,
						table:table
					}
				}
			);
		}
	};
})
.controller("ListController",function($scope,ItemApi){
	$scope.items=[];
	function LoadData() {
		ItemApi.getItems()
		.then(function(result){
			$scope.items=result.data.docs;
		})
		.catch(function(err){
			console.log('get err',err);
			$scope.status={
				text:'Error loading data '+newItem.text+' '+ err,
				error:true
			};
		});
	}
	$scope.AddItem = function() {
		var newItem={text:$scope.newItemText,completed:false};
		ItemApi.saveItem(newItem)
		.then(function(){
			$scope.newItemText='';
			LoadData();
		})
		.catch(function(err){
			console.log('add err',err);
			$scope.status={
				text:'Error saving item '+newItem.text+' '+ err,
				error:true
			};
		});
	};
	$scope.RemoveItem=function(_item) {
		ItemApi.deleteItem(_item)
		.then(function(){
			LoadData();
		})
		.catch(function(err){
			console.log('delete err',err);
			$scope.status={
				text:'Error deleting item '+newItem.text+' '+ err,
				error:true
			};
		});
	}
	$scope.SaveItem = function(itm) {
		ItemApi.saveItem(itm)
		.then(function(){
			LoadData();
		})
		.catch(function(err){
			console.log('save err',err);
			$scope.status={
				text:'Error saving item '+newItem.text+' '+ err,
				error:true
			};
		});
	};
	LoadData();
})
.controller("ItemController",function($scope){

});