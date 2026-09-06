package com.example.englishvocab.repository;

import com.example.englishvocab.entity.Vocabulary;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import java.util.List;

public interface VocabularyRepository extends JpaRepository<Vocabulary, Integer> {
    List<Vocabulary> findByTopic_TopicIDOrderByWordAsc(Integer topicId);

    long countByTopic_TopicID(Integer topicId);

    @Query("SELECT v FROM Vocabulary v WHERE LOWER(v.word) LIKE LOWER(CONCAT('%', :q, '%'))")
    List<Vocabulary> searchByWord(String q);
}
