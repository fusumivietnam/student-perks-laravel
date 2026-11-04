#!/usr/bin/env node
// Manual GitHub Project V2 setup; no external npm dependencies.
'use strict';
const token=process.env.PROJECT_TOKEN;
const apply=process.env.APPLY==='true';
if(!token){console.error('Missing PROJECT_V2_TOKEN secret');process.exit(1);}
const owner='fusumivietnam', repo='student-perks-laravel', projectNumber=5;
const issueNumbers=[5,1,2,3,4,6];
async function gql(query,variables={}){
 const r=await fetch('https://api.github.com/graphql',{method:'POST',headers:{
  Authorization:'Bearer '+token,Accept:'application/vnd.github+json',
  'Content-Type':'application/json','User-Agent':'student-perks-project-setup'
 },body:JSON.stringify({query,variables})});
 const j=await r.json();
 if(!r.ok||j.errors?.length)throw Error('GraphQL failure: '+JSON.stringify(j.errors?.map(e=>e.message)||j.message));
 return j.data;
}
const projectQuery='query($login:String!,$number:Int!){user(login:$login){projectV2(number:$number){id title url items(first:100){nodes{id content{... on Issue{number repository{nameWithOwner}}}}}}}}';
const issueQuery='query($owner:String!,$repo:String!,$number:Int!){repository(owner:$owner,name:$repo){issue(number:$number){id url}}}';
const addQuery='mutation($project:ID!,$content:ID!){addProjectV2ItemById(input:{projectId:$project,contentId:$content}){item{id}}}';
(async()=>{
 const data=await gql(projectQuery,{login:owner,number:projectNumber});
 const project=data.user?.projectV2;
 if(!project)throw Error('Project #5 inaccessible: check PAT project scope and account.');
 console.log('Project accessible: '+project.url+' ('+project.title+')');
 const known=new Set(project.items.nodes.filter(n=>n?.content?.repository?.nameWithOwner===owner+'/'+repo).map(n=>n.content.number));
 for(const number of issueNumbers){
  const r=await gql(issueQuery,{owner,repo,number});
  if(!r.repository?.issue)throw Error('Issue #'+number+' not found');
  if(known.has(number)){console.log('Exists: issue #'+number);continue;}
  if(!apply){console.log('Would add: issue #'+number);continue;}
  await gql(addQuery,{project:project.id,content:r.repository.issue.id});
  console.log('Added issue #'+number);
 }
 console.log(apply?'Applied Project item associations.':'Read-only check succeeded; rerun with apply=true to add items.');
})().catch(e=>{console.error(e.message);process.exitCode=1;});
